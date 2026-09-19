<?php

namespace Go_Live_Update_Urls;

/**
 * Rewrite the URLs inside a raw serialized string without decoding it.
 *
 * Walks the serialized bytes and passes only the values of `s:` tokens
 * through the replacement closure, recalculating their lengths. When given
 * a number closure, `i:` and `d:` values pass through it too. Every other
 * byte is copied verbatim, so references, floats, enums, deep nesting,
 * and UTF-8 all survive byte for byte.
 *
 * `unserialize` is never called, so crafted objects are never instantiated.
 *
 * @author OnPoint Plugins
 * @since  7.1.0
 */
class Serialized_Parser {
	/**
	 * Deepest nesting we will follow before bailing.
	 *
	 * PHP's default `unserialize_max_depth` is 4096, but we
	 * don't want to risk exhausting the call stack, so we
	 * track nesting on an explicit stack instead of by recursion.
	 *
	 * @var int
	 */
	protected const MAX_DEPTH = 4096;

	/**
	 * Most digits a count or length may have, so it always fits in an integer.
	 *
	 * Digits past these are left unread, so the byte after fails its check.
	 *
	 * @var int
	 */
	protected const MAX_DIGITS = 18;

	/**
	 * Most unchanged bytes copied at once, so a long span never doubles
	 * the memory a rewrite holds.
	 *
	 * @var int
	 */
	protected const CHUNK = 8192;

	/**
	 * Pattern a `d:` token's body must match to be valid.
	 *
	 * @var string
	 */
	protected const FLOAT_PATTERN = '/^(?:-?INF|NAN|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)$/D';

	/**
	 * `d:` bodies which `(float)` reads as `0`.
	 *
	 * @var string[]
	 */
	protected const NON_FINITE = [ 'INF', '-INF', 'NAN' ];

	/**
	 * Receives an `s:` token's value and returns its replacement.
	 *
	 * @phpstan-var \Closure(string):string
	 *
	 * @var \Closure
	 */
	protected \Closure $replacer;

	/**
	 * Receives an `i:` or `d:` value as text and returns its replacement.
	 *
	 * @phpstan-var (\Closure(string):string)|null
	 *
	 * @var \Closure|null
	 */
	protected ?\Closure $number_replacer;

	/**
	 * Did the last rewrite run across a token we can't support?
	 *
	 * @var bool
	 */
	protected bool $unsupported = false;


	/**
	 * Serialized_Parser constructor.
	 *
	 * @phpstan-param \Closure(string):string        $replacer
	 * @phpstan-param (\Closure(string):string)|null $number_replacer
	 *
	 * @param \Closure                               $replacer        - Called with each `s:` token's value.
	 * @param \Closure|null                          $number_replacer - Called with each `i:` or `d:` value.
	 */
	final protected function __construct( \Closure $replacer, ?\Closure $number_replacer ) {
		$this->replacer = $replacer;
		$this->number_replacer = $number_replacer;
	}


	/**
	 * Rewrite a serialized string in place.
	 *
	 * The replace closures may rewrite another string with this parser
	 * before the first rewrite returns.
	 *
	 * @param string        $serialized - Raw serialized value from a database column.
	 * @param string[]|null $needles    - Only `s:` values holding one of these reach the replace closure. `null` for every value.
	 *
	 * @return string|null - `null` when the value can't be parsed.
	 */
	public function rewrite( string $serialized, ?array $needles = null ): ?string {
		$unsupported = false;
		$result = $this->parse( $serialized, $needles, $unsupported );
		$this->unsupported = $unsupported;

		return $result;
	}


	/**
	 * Did the last call to `rewrite` run across a token we copied
	 * verbatim because we can't safely rewrite it?
	 *
	 * @return bool
	 */
	public function has_unsupported(): bool {
		return $this->unsupported;
	}


	/**
	 * Walk every token of a single value in one loop.
	 *
	 * Nesting is tracked on an explicit stack instead of by recursion, so
	 * deep data can't exhaust the call stack. `$left` counts the keys and
	 * values the innermost open `a:` or `O:` still expects, and the stack
	 * holds the counts of the surrounding containers.
	 *
	 * Unchanged bytes are only copied when a later token changes. Helpers
	 * never take a loop variable by reference, which would slow every
	 * later use of it.
	 *
	 * @param string        $data        - Raw serialized value.
	 * @param string[]|null $needles     - Only `s:` values holding one of these reach the replace closure.
	 * @param bool          $unsupported - Set to `true` when a token can't be rewritten safely.
	 *
	 * @return string|null
	 */
	protected function parse( string $data, ?array $needles, bool &$unsupported ): ?string {
		$filtered = \is_array( $needles );
		// Offset of each needle's next match, and of the nearest one. Needles missing from the data are dropped.
		$found = [];
		$next = [];
		$nearest = - 1;
		if ( $filtered ) {
			$nearest = \PHP_INT_MAX;
			foreach ( $needles as $needle ) {
				$match = \strpos( $data, $needle );
				if ( \is_int( $match ) ) {
					$found[] = $needle;
					$next[] = $match;
					$nearest = \min( $nearest, $match );
				}
			}
		}
		$holds = false;

		$replacer = $this->replacer;
		$number_replacer = $this->number_replacer;
		$bytes = \strlen( $data );
		$output = '';
		$copied = 0;
		$position = 0;
		$stack = [];
		$level = 0;
		$left = 1;
		while ( true ) {
			if ( 0 === $left ) {
				if ( 0 === $level ) {
					break;
				}
				if ( $bytes <= $position || '}' !== $data[ $position ] ) {
					return null;
				}
				++ $position;
				-- $level;
				$left = $stack[ $level ];
				continue;
			}

			$start = $position;
			$head = \substr( $data, $position, 2 );
			$position += 2;
			// Keys and values alternate, so an even count left means this token is a value.
			-- $left;
			if ( 's:' === $head ) {
				$digits = \strspn( $data, '0123456789', $position, self::MAX_DIGITS );
				if ( 0 === $digits ) {
					return null;
				}
				$from = $position + $digits + 2;
				$close = $from + (int) \substr( $data, $position, $digits );
				if ( ':"' !== \substr( $data, $from - 2, 2 ) || '";' !== \substr( $data, $close, 2 ) ) {
					return null;
				}
				$position = $close + 2;

				// No needle starts inside the value.
				if ( $close <= $nearest ) {
					continue;
				}
				if ( $filtered ) {
					$nearest = $this->advance_needles( $data, $from, $close, $found, $next, $holds );
					if ( ! $holds ) {
						continue;
					}
				}

				$value = \substr( $data, $from, $close - $from );
				// Names holding a NUL are mangled private or protected properties, which `trim` would corrupt.
				if ( 0 === ( $left & 1 ) || ! \str_contains( $value, "\0" ) ) {
					$replaced = $replacer( $value );
					if ( $replaced !== $value ) {
						// A short span needs no chunks.
						if ( $start - $copied <= self::CHUNK ) {
							$output .= \substr( $data, $copied, $start - $copied );
						} else {
							$this->copy( $output, $data, $copied, $start );
						}
						$output .= 's:' . \strlen( $replaced ) . ':"' . $replaced . '";';
						$copied = $position;
					}
				}
			} elseif ( 'i:' === $head || 'd:' === $head ) {
				// Integer keys are too common to leave to a helper.
				if ( 'i:' === $head ) {
					$from = $position;
					if ( $position < $bytes && '-' === $data[ $position ] ) {
						++ $from;
					}
					$end = $from + \strspn( $data, '0123456789', $from );
					if ( $from === $end || $bytes <= $end || ';' !== $data[ $end ] ) {
						return null;
					}
				} else {
					$end = $this->find_float_end( $data, $position );
					if ( null === $end ) {
						return null;
					}
				}
				if ( 0 === ( $left & 1 ) && $number_replacer instanceof \Closure ) {
					$replaced = $this->replace_number( $head[0], \substr( $data, $position, $end - $position ), $number_replacer );
					if ( \is_string( $replaced ) ) {
						$this->copy( $output, $data, $copied, $start );
						$output .= $replaced;
						$copied = $end + 1;
					}
				}
				$position = $end + 1;
			} elseif ( 'a:' === $head || 'O:' === $head ) {
				if ( 'O:' === $head ) {
					$end = $this->skip_quoted( $data, $position, '":' );
					if ( null === $end ) {
						return null;
					}
					$position = $end;
				}
				$digits = \strspn( $data, '0123456789', $position, self::MAX_DIGITS );
				if ( 0 === $digits || self::MAX_DEPTH <= $level || ':{' !== \substr( $data, $position + $digits, 2 ) ) {
					return null;
				}
				$stack[ $level ] = $left;
				++ $level;
				$left = 2 * (int) \substr( $data, $position, $digits );
				$position += $digits + 2;
			} elseif ( 'N;' === $head ) {
				continue;
			} elseif ( 'C:' === $head ) {
				// A legacy `Serializable` body is opaque, so it is copied verbatim.
				$end = $this->skip_serializable( $data, $position );
				if ( null === $end ) {
					return null;
				}
				$position = $end;
				$unsupported = true;
			} else {
				$end = $this->skip_token( $head, $data, $position );
				if ( null === $end ) {
					return null;
				}
				$position = $end;
			}
		}

		if ( $bytes !== $position ) {
			return null;
		}
		if ( 0 === $copied ) {
			return $data;
		}
		$this->copy( $output, $data, $copied, $position );

		return $output;
	}


	/**
	 * Move each needle's next match past an `s:` value.
	 *
	 * @phpstan-param list<string> $found
	 * @phpstan-param list<int>    $next
	 *
	 * @param string               $data  - Raw serialized value.
	 * @param int                  $from  - Offset of the value's first byte.
	 * @param int                  $close - Offset after the value's last byte.
	 * @param string[]             $found - Needles the data holds.
	 * @param int[]                $next  - Offset of each needle's next match.
	 * @param bool                 $holds - Set to whether a needle lies wholly inside the value.
	 *
	 * @return int - Offset of the nearest match left.
	 */
	protected function advance_needles( string $data, int $from, int $close, array $found, array &$next, bool &$holds ): int {
		$holds = false;
		$nearest = \PHP_INT_MAX;
		foreach ( $found as $i => $needle ) {
			if ( $next[ $i ] < $from ) {
				$match = \strpos( $data, $needle, $from );
				$next[ $i ] = false === $match ? \PHP_INT_MAX : $match;
			}
			if ( $next[ $i ] < $close ) {
				if ( $next[ $i ] <= $close - \strlen( $needle ) ) {
					$holds = true;
				}
				// Only a match past this value can be inside a later one.
				$match = \strpos( $data, $needle, $close );
				$next[ $i ] = false === $match ? \PHP_INT_MAX : $match;
			}
			if ( $next[ $i ] < $nearest ) {
				$nearest = $next[ $i ];
			}
		}

		return $nearest;
	}


	/**
	 * Find the `;` which closes a `d:` token.
	 *
	 * @param string $data     - Raw serialized value.
	 * @param int    $position - Offset after the `d:`.
	 *
	 * @return int|null - `null` when the body isn't a float.
	 */
	protected function find_float_end( string $data, int $position ): ?int {
		$end = \strpos( $data, ';', $position );
		if ( \is_int( $end ) && 1 === \preg_match( self::FLOAT_PATTERN, \substr( $data, $position, $end - $position ) ) ) {
			return $end;
		}

		return null;
	}


	/**
	 * Skip a `b:`, `r:`, `R:` or `E:` token, none of which can hold a URL.
	 *
	 * @param string $head     - First two bytes of the token.
	 * @param string $data     - Raw serialized value.
	 * @param int    $position - Offset after the head.
	 *
	 * @return int|null - Offset after the token, or `null` when it isn't valid.
	 */
	protected function skip_token( string $head, string $data, int $position ): ?int {
		if ( 'b:' === $head ) {
			$body = \substr( $data, $position, 2 );
			if ( '0;' === $body || '1;' === $body ) {
				return $position + 2;
			}
		} elseif ( 'r:' === $head || 'R:' === $head ) {
			$end = $position + \strspn( $data, '0123456789', $position );
			if ( $position < $end && $end < \strlen( $data ) && ';' === $data[ $end ] ) {
				return $end + 1;
			}
		} elseif ( 'E:' === $head ) {
			return $this->skip_quoted( $data, $position, '";' );
		}

		return null;
	}


	/**
	 * Skip the `LEN:"<class>":N:{<N bytes>}` which follows a `C:`.
	 *
	 * @param string $data     - Raw serialized value.
	 * @param int    $position - Offset after the `C:`.
	 *
	 * @return int|null - Offset after the `}`, or `null` when it isn't valid.
	 */
	protected function skip_serializable( string $data, int $position ): ?int {
		$size = $this->skip_quoted( $data, $position, '":' );
		if ( null === $size ) {
			return null;
		}
		$digits = \strspn( $data, '0123456789', $size, self::MAX_DIGITS );
		$close = $size + $digits + 2 + (int) \substr( $data, $size, $digits );
		if ( 0 < $digits && ':{' === \substr( $data, $size + $digits, 2 ) && $close < \strlen( $data ) && '}' === $data[ $close ] ) {
			return $close + 1;
		}

		return null;
	}


	/**
	 * Skip `LEN:"<LEN bytes>"` and the byte which must follow it.
	 *
	 * Byte counts only. `mb_*` would read the declared length as
	 * characters and walk off the end of the token.
	 *
	 * @param string $data     - Raw serialized value.
	 * @param int    $position - Offset of `LEN`.
	 * @param string $closer   - Closing quote and the byte which must follow it.
	 *
	 * @return int|null - Offset after the closer, or `null` when it isn't valid.
	 */
	protected function skip_quoted( string $data, int $position, string $closer ): ?int {
		$digits = \strspn( $data, '0123456789', $position, self::MAX_DIGITS );
		$from = $position + $digits + 2;
		$close = $from + (int) \substr( $data, $position, $digits );
		if ( 0 < $digits && ':"' === \substr( $data, $from - 2, 2 ) && \substr( $data, $close, 2 ) === $closer ) {
			return $close + 2;
		}

		return null;
	}


	/**
	 * Append the unchanged bytes between two offsets.
	 *
	 * @param string $output - Rewritten value so far.
	 * @param string $data   - Raw serialized value.
	 * @param int    $from   - Offset of the first byte to copy.
	 * @param int    $to     - Offset after the last byte to copy.
	 *
	 * @return void
	 */
	protected function copy( string &$output, string $data, int $from, int $to ): void {
		for ( ; $from < $to; $from += self::CHUNK ) {
			$output .= \substr( $data, $from, \min( self::CHUNK, $to - $from ) );
		}
	}


	/**
	 * Send an `i:` or `d:` value's text through the number closure.
	 *
	 * The text matches the `(string)` cast of the decoded number. A changed
	 * number keeps its token while the result is still valid for it,
	 * otherwise it becomes an `s:`.
	 *
	 * @phpstan-param \Closure(string):string $number_replacer
	 *
	 * @param string                          $token           - `i` or `d`.
	 * @param string                          $body            - Bytes between the `:` and the `;`.
	 * @param \Closure                        $number_replacer - Called with the number's text.
	 *
	 * @return string|null - Replacement token, or `null` when the number is unchanged.
	 */
	protected function replace_number( string $token, string $body, \Closure $number_replacer ): ?string {
		$number = $body;
		if ( 'd' === $token && ! \in_array( $body, self::NON_FINITE, true ) ) {
			$number = (string) (float) $body;
		}

		$replaced = $number_replacer( $number );
		if ( $replaced === $number ) {
			return null;
		}
		if ( $this->is_valid_number( $token, $replaced ) ) {
			return $token . ':' . $replaced . ';';
		}

		return 's:' . \strlen( $replaced ) . ':"' . $replaced . '";';
	}


	/**
	 * Can the text be written as the body of an `i:` or `d:` token?
	 *
	 * An `i:` body must be exactly how PHP writes an integer it can hold,
	 * which rejects overflow, leading zeros, `+8` and `-0`.
	 *
	 * @param string $token  - `i` or `d`.
	 * @param string $number - Text to check.
	 *
	 * @return bool
	 */
	protected function is_valid_number( string $token, string $number ): bool {
		if ( 'd' === $token ) {
			return 1 === \preg_match( self::FLOAT_PATTERN, $number );
		}
		$int = \filter_var( $number, \FILTER_VALIDATE_INT );
		return \is_int( $int ) && (string) $int === $number;
	}


	/**
	 * Create a new instance of the parser with the given replace closures.
	 *
	 * Without a number closure, `i:` and `d:` tokens are copied verbatim.
	 *
	 * @phpstan-param \Closure(string):string        $replacer
	 * @phpstan-param (\Closure(string):string)|null $number_replacer
	 *
	 * @param \Closure                               $replacer        - Called with each `s:` token's value.
	 * @param \Closure|null                          $number_replacer - Called with each `i:` or `d:` value.
	 *
	 * @return static
	 */
	public static function factory( \Closure $replacer, ?\Closure $number_replacer = null ): Serialized_Parser {
		return new static( $replacer, $number_replacer );
	}
}
