<?php

namespace Go_Live_Update_Urls;

/**
 * Rewrite the URLs inside a raw serialized string without decoding it.
 *
 * Walks the serialized bytes and passes only the values of `s:` tokens
 * through the replacement closure, recalculating their lengths. Every other
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
	 * Patterns each scalar token's body must match to be valid.
	 *
	 * @var array<string, string>
	 */
	protected const SCALAR_PATTERNS = [
		'b' => '/^[01]$/',
		'd' => '/^(?:-?INF|NAN|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)$/',
		'i' => '/^-?\d+$/',
		'r' => '/^\d+$/',
		'R' => '/^\d+$/',
	];

	/**
	 * Receives an `s:` token's value and returns its replacement.
	 *
	 * @phpstan-var \Closure(string):string
	 *
	 * @var \Closure
	 */
	protected \Closure $replacer;

	/**
	 * Serialized string currently being rewritten.
	 *
	 * @var string
	 */
	protected string $data = '';

	/**
	 * Byte offset of the next unread character within `$data`.
	 *
	 * @var int
	 */
	protected int $position = 0;

	/**
	 * Did the current rewrite run across a token we can't support?
	 *
	 * @var bool
	 */
	protected bool $unsupported = false;


	/**
	 * Serialized_Parser constructor.
	 *
	 * @phpstan-param \Closure(string):string $replacer
	 *
	 * @param \Closure                        $replacer - Called with each `s:` token's value.
	 */
	final protected function __construct( \Closure $replacer ) {
		$this->replacer = $replacer;
	}


	/**
	 * Rewrite a serialized string in place.
	 *
	 * @param string $serialized - Raw serialized value from a database column.
	 *
	 * @return string|null - `null` when the value can't be parsed.
	 */
	public function rewrite( string $serialized ): ?string {
		$this->data = $serialized;
		$this->position = 0;
		$this->unsupported = false;

		$result = $this->parse();
		if ( null === $result || \strlen( $this->data ) !== $this->position ) {
			return null;
		}

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
	 * Walk every token of a single value.
	 *
	 * Nesting is tracked on an explicit stack instead of by recursion, so
	 * deep data can't exhaust the call stack. Output is only ever appended
	 * in order, so the stack need only count the values each open `a:` or
	 * `O:` still expects. The bottom entry is the lone top level value.
	 *
	 * @return string|null
	 */
	protected function parse(): ?string {
		$output = '';
		$remaining = [ 1 ];
		while ( [] !== $remaining ) {
			$level = \count( $remaining ) - 1;
			if ( 0 === $remaining[ $level ] ) {
				\array_pop( $remaining );
				if ( 0 < $level ) {
					if ( '}' !== $this->byte() ) {
						return null;
					}
					++ $this->position;
					$output .= '}';
					-- $remaining[ $level - 1 ];
				}
				continue;
			}

			$token = $this->byte();
			if ( 'a' === $token || 'O' === $token ) {
				if ( self::MAX_DEPTH <= $level ) {
					return null;
				}
				$start = $this->position;
				$count = 'a' === $token ? $this->read_array_header() : $this->read_object_header();
				if ( null === $count ) {
					return null;
				}
				$output .= \substr( $this->data, $start, $this->position - $start );
				$remaining[] = $count * 2;
				continue;
			}

			$value = $this->parse_leaf( $token );
			if ( null === $value ) {
				return null;
			}
			$output .= $value;
			-- $remaining[ $level ];
		}

		return $output;
	}


	/**
	 * Parse a token which can't hold other values.
	 *
	 * @param string $token - Single character token at the current position.
	 *
	 * @return string|null
	 */
	protected function parse_leaf( string $token ): ?string {
		if ( 's' === $token ) {
			return $this->parse_string();
		}
		if ( 'C' === $token ) {
			return $this->parse_custom();
		}
		if ( 'E' === $token ) {
			return $this->parse_enum();
		}
		if ( 'N' === $token ) {
			return $this->parse_null();
		}
		if ( isset( self::SCALAR_PATTERNS[ $token ] ) ) {
			return $this->parse_scalar();
		}

		return null;
	}


	/**
	 * Parse `s:LEN:"<LEN bytes>";` and send its value through the
	 * replace closure.
	 *
	 * Values holding a NUL byte are private or protected property names
	 * mangled as `\0Class\0prop` or `\0*\0prop`. Replacements `trim` their
	 * result and NUL is in `trim`'s default charlist, so these are copied
	 * verbatim instead.
	 *
	 * @return string|null
	 */
	protected function parse_string(): ?string {
		$length = $this->read_header( 's' );
		if ( null === $length ) {
			return null;
		}
		$value = $this->read_quoted( $length );
		if ( null === $value || ';' !== $this->byte() ) {
			return null;
		}
		++ $this->position;

		if ( \str_contains( $value, "\0" ) ) {
			return 's:' . $length . ':"' . $value . '";';
		}
		$replaced = ( $this->replacer )( $value );

		return 's:' . \strlen( $replaced ) . ':"' . $replaced . '";';
	}


	/**
	 * Read the `a:N:{` which opens an array.
	 *
	 * @return int|null - Number of key/value pairs the array holds.
	 */
	protected function read_array_header(): ?int {
		$count = $this->read_header( 'a' );
		if ( null === $count || '{' !== $this->byte() ) {
			return null;
		}
		++ $this->position;

		return $count;
	}


	/**
	 * Read the `O:LEN:"<class>":N:{` which opens an object.
	 *
	 * `N` counts properties rather than bytes, so rewriting a string
	 * inside the body never invalidates the header.
	 *
	 * @return int|null - Number of property/value pairs the object holds.
	 */
	protected function read_object_header(): ?int {
		$name_length = $this->read_header( 'O' );
		if ( null === $name_length || null === $this->read_quoted( $name_length ) || ':' !== $this->byte() ) {
			return null;
		}
		++ $this->position;
		$count = $this->read_digits();
		if ( null === $count || '{' !== $this->byte() ) {
			return null;
		}
		++ $this->position;

		return $count;
	}


	/**
	 * Parse the legacy `C:LEN:"<class>":BODY:{<BODY bytes>}` written by
	 * classes implementing `Serializable`.
	 *
	 * The body is opaque, so it is copied verbatim and the row is
	 * reported as unsupported.
	 *
	 * @return string|null
	 */
	protected function parse_custom(): ?string {
		$start = $this->position;
		$name_length = $this->read_header( 'C' );
		if ( null === $name_length || null === $this->read_quoted( $name_length ) || ':' !== $this->byte() ) {
			return null;
		}
		++ $this->position;
		$body_length = $this->read_digits();
		if ( null === $body_length || '{' !== $this->byte() || \strlen( $this->data ) < $this->position + $body_length + 2 ) {
			return null;
		}
		$this->position += $body_length + 2;
		if ( '}' !== $this->byte( - 1 ) ) {
			return null;
		}
		$this->unsupported = true;

		return \substr( $this->data, $start, $this->position - $start );
	}


	/**
	 * Parse `E:LEN:"<enum>:<case>";` verbatim.
	 *
	 * @return string|null
	 */
	protected function parse_enum(): ?string {
		$start = $this->position;
		$length = $this->read_header( 'E' );
		if ( null === $length || null === $this->read_quoted( $length ) || ';' !== $this->byte() ) {
			return null;
		}
		++ $this->position;

		return \substr( $this->data, $start, $this->position - $start );
	}


	/**
	 * Parse `N;` verbatim.
	 *
	 * @return string|null
	 */
	protected function parse_null(): ?string {
		if ( 'N;' !== \substr( $this->data, $this->position, 2 ) ) {
			return null;
		}
		$this->position += 2;

		return 'N;';
	}


	/**
	 * Parse `b:`, `d:`, `i:`, `r:`, or `R:` verbatim.
	 *
	 * None of these may hold a `;`, so the token ends at the first one.
	 *
	 * @return string|null
	 */
	protected function parse_scalar(): ?string {
		$start = $this->position;
		$token = $this->byte();
		if ( ':' !== $this->byte( 1 ) ) {
			return null;
		}
		$end = \strpos( $this->data, ';', $this->position + 2 );
		if ( false === $end ) {
			return null;
		}
		$body = \substr( $this->data, $this->position + 2, $end - $this->position - 2 );
		if ( 1 !== \preg_match( self::SCALAR_PATTERNS[ $token ], $body ) ) {
			return null;
		}
		$this->position = $end + 1;

		return \substr( $this->data, $start, $this->position - $start );
	}


	/**
	 * Read a `<token>:<digits>:` header.
	 *
	 * @param string $token - Single character token the header must start with.
	 *
	 * @return int|null
	 */
	protected function read_header( string $token ): ?int {
		if ( $token !== $this->byte() || ':' !== $this->byte( 1 ) ) {
			return null;
		}
		$this->position += 2;

		return $this->read_digits();
	}


	/**
	 * Read `<digits>:` from the current position.
	 *
	 * @return int|null
	 */
	protected function read_digits(): ?int {
		$end = \strpos( $this->data, ':', $this->position );
		if ( false === $end ) {
			return null;
		}
		$digits = \substr( $this->data, $this->position, $end - $this->position );
		if ( ! \ctype_digit( $digits ) ) {
			return null;
		}
		$this->position = $end + 1;

		return (int) $digits;
	}


	/**
	 * Read `"<length bytes>"` from the current position.
	 *
	 * Byte counts only. `mb_*` would read the declared length as
	 * characters and walk off the end of the token.
	 *
	 * @param int $length - Number of bytes declared between the quotes.
	 *
	 * @return string|null
	 */
	protected function read_quoted( int $length ): ?string {
		if ( '"' !== $this->byte() ) {
			return null;
		}
		$value = \substr( $this->data, $this->position + 1, $length );
		if ( \strlen( $value ) !== $length || '"' !== $this->byte( $length + 1 ) ) {
			return null;
		}
		$this->position += $length + 2;

		return $value;
	}


	/**
	 * Get a single byte relative to the current position.
	 *
	 * @param int $offset - Bytes past the current position.
	 *
	 * @return string - Empty when the offset falls outside the data.
	 */
	protected function byte( int $offset = 0 ): string {
		return $this->data[ $this->position + $offset ] ?? '';
	}


	/**
	 * Create a new instance of the parser with the given replace closure.
	 *
	 * @phpstan-param \Closure(string):string $replacer
	 *
	 * @param \Closure                        $replacer - Called with each `s:` token's value.
	 *
	 * @return static
	 */
	public static function factory( \Closure $replacer ): Serialized_Parser {
		return new static( $replacer );
	}
}
