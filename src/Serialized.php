<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Updaters\Repo;

/**
 * Serialized data handling.
 *
 * Serialized strings are rewritten by `Serialized_Parser` and never
 * decoded, so objects stored in the database are never instantiated.
 *
 * @phpstan-type REPLACEMENT array{
 *     old: list<string>,
 *     new: list<string>,
 *     counted: bool
 * }
 *
 * @author OnPoint Plugins
 * @since  6.0.0
 */
class Serialized {
	/**
	 * Logged when a row can't be parsed.
	 *
	 * @var string
	 */
	protected const UNPARSEABLE = 'it could not be parsed as serialized data';

	/**
	 * Logged when a row holds a `C:` body, which is opaque.
	 *
	 * @var string
	 */
	protected const LEGACY = 'it contains a legacy `Serializable` object which cannot be updated safely';

	/**
	 * New URL
	 *
	 * @var string
	 */
	protected $new;

	/**
	 * Old URL
	 *
	 * @var string
	 */
	protected $old;

	/**
	 * Hold replacement count during a table update.
	 * We may replace multiple per table row, so we count
	 * the actual str_replace() instead of mysql affected.
	 *
	 * @var int
	 */
	protected $count = 0;

	/**
	 * Setting dry run to `true` will prevent any data
	 * from being updated in the database but still run
	 * through the process and return counts of would
	 * have been updated of dry run was `false`.
	 *
	 * @var bool
	 */
	protected $dry_run = false;

	/**
	 * Did the value being rewritten hit a token we can't update safely?
	 *
	 * Tracked per value, because `Skip_Rows` spans every column of a row.
	 *
	 * @var bool
	 */
	protected bool $row_skipped = false;

	/**
	 * Search and replace URLs, computed once per run.
	 *
	 * @phpstan-var non-empty-list<REPLACEMENT>|null
	 * @var array<int, array<string, string[]|bool>>|null
	 */
	protected ?array $replacements = null;

	/**
	 * Rewrites every serialized string, nested ones included.
	 *
	 * @var Serialized_Parser
	 */
	protected Serialized_Parser $parser;


	/**
	 * Serialized constructor.
	 *
	 * @param string $old_url - Old URL.
	 * @param string $new_url - New URL.
	 */
	public function __construct( $old_url, $new_url ) {
		$this->new = $new_url;
		$this->old = $old_url;

		$number_replacer = null;
		if ( \is_numeric( $old_url ) ) {
			$number_replacer = \Closure::fromCallable( [ $this, 'replace_number' ] );
		}
		$this->parser = Serialized_Parser::factory( \Closure::fromCallable( [ $this, 'replace' ] ), $number_replacer );
	}


	/**
	 * Go through every registered serialized table and update them one by one
	 *
	 * @since 5.2.5 - Only update provided tables.
	 *
	 * @param array<int, string> $tables - The tables to update.
	 *
	 * @return array<string, int>
	 */
	public function update_all_serialized_tables( array $tables ) {
		$serialized_tables = Database::instance()->get_serialized_tables();

		$counts = [];
		foreach ( $serialized_tables as $table => $columns ) {
			if ( ! \in_array( $table, $tables, true ) ) {
				continue;
			}
			$counts[ $table ] = \array_sum( \array_map( function( $column ) use ( $table ) {
				return $this->update_table( $table, $column );
			}, (array) $columns ) );
		}
		return $counts;
	}


	/**
	 * Query all serialized rows from a database table and
	 * update them one by one.
	 *
	 * @noinspection CallableParameterUseCaseInTypeContextInspection
	 *
	 * @param string $table  - Database table.
	 * @param string $column - Database column.
	 *
	 * @return int
	 */
	protected function update_table( string $table, string $column ): int {
		global $wpdb;
		$this->count = 0;
		if ( ! $wpdb instanceof \wpdb ) {
			return $this->count;
		}

		$column = esc_sql( $column );
		$table = esc_sql( $table );
		$pk = $wpdb->get_results( $wpdb->prepare( "SHOW KEYS FROM %i WHERE Key_name = 'PRIMARY'", $table ) );
		if ( ! isset( $pk[0]->Column_name ) || '' === $pk[0]->Column_name ) {
			$pk = $wpdb->get_results( $wpdb->prepare( 'SHOW KEYS FROM %i', $table ) );
			if ( ! isset( $pk[0]->Column_name ) || '' === $pk[0]->Column_name ) {
				return $this->count; // failed.
			}
		}
		$primary_key_column = $pk[0]->Column_name;
		Skip_Rows::instance()->set_current_table( $table, $primary_key_column );

		// Get all serialized rows.
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT %i, %i FROM %i WHERE %i LIKE %s OR %i LIKE %s OR %i LIKE %s;',
			$primary_key_column, $column, $table, $column, 'a:%', $column, 'O:%', $column, 's:%'
		) );
		if ( ! \is_array( $rows ) || [] === $rows ) {
			return $this->count; // failed.
		}

		foreach ( $rows as $row ) {
			if ( ! isset( $row->{$primary_key_column}, $row->{$column} ) ) {
				continue;
			}
			Skip_Rows::instance()->set_current_row_id( $row->{$primary_key_column} );
			$clean = $this->rewrite_row( $row->{$column} );
			if ( \is_string( $clean ) && ! $this->dry_run ) {
				$query = $wpdb->prepare(
					'UPDATE %i SET %i=%s WHERE %i = %s',
					$table, $column, $clean, $primary_key_column, $row->{$primary_key_column}
				);
				if ( null === $query ) {
					continue; // failed.
				}
				$wpdb->query( $query );
			}
		}

		return $this->count;
	}


	/**
	 * Rewrite a single database row's value.
	 *
	 * @param string $value - Raw value from the database column.
	 *
	 * @return string|null - `null` when the row has nothing to write.
	 */
	protected function rewrite_row( string $value ): ?string {
		if ( ! $this->has_data_to_update( $value ) ) {
			return null;
		}

		$count = $this->count;
		$this->row_skipped = false;
		$clean = $this->rewrite( $value );
		if ( $this->row_skipped ) {
			// Nothing in a skipped row is written.
			$this->count = $count;
			return null;
		}
		if ( $clean === $value ) {
			return null;
		}

		return $clean;
	}


	/**
	 * Rewrite a serialized string without decoding it.
	 *
	 * Skips the current row and returns the value unchanged when it
	 * can't be parsed or holds a legacy `C:` body.
	 *
	 * @phpstan-impure
	 *
	 * @param string $serialized - Raw serialized value.
	 *
	 * @return string
	 */
	protected function rewrite( string $serialized ): string {
		$result = $this->parser->rewrite( $serialized, $this->get_old_urls() );
		if ( null === $result ) {
			$this->skip_current_row( self::UNPARSEABLE );
			return $serialized;
		}
		if ( $this->parser->has_unsupported() ) {
			$this->skip_current_row( self::LEGACY );
			return $serialized;
		}

		return $result;
	}


	/**
	 * Replace a number only when its whole value is the old value.
	 *
	 * @param string $number - Number as text.
	 *
	 * @return string
	 */
	protected function replace_number( string $number ): string {
		if ( $this->old === $number ) {
			++ $this->count;
			return $this->new;
		}
		return $number;
	}


	/**
	 * Skip and log the current row once, no matter how many
	 * of its values can't be updated.
	 *
	 * @param string $reason - Completes "because ..." in the log.
	 *
	 * @return void
	 */
	protected function skip_current_row( string $reason ): void {
		$this->row_skipped = true;
		Skip_Rows::instance()->skip_current_once( $reason );
	}


	/**
	 * Replaces all the occurrences of a string in a multidimensional array or Object
	 *
	 * @since      5.2.0
	 * @deprecated 7.1.0 Will be removed in version 8.
	 *
	 * @param object|array<int|string, mixed>|string|int|float|bool|null $data - Data to change.
	 *
	 * @return object|array<int|string, mixed>|string|int|float|bool|null
	 */
	public function replace_tree( $data ) {
		_deprecated_function( __METHOD__, '7.1.0' );
		return $this->replace_decoded( $data );
	}


	/**
	 * Replaces all the occurrences of a string in decoded data.
	 *
	 * @noinspection OffsetOperationsInspection
	 *
	 * @param object|array<int|string, mixed>|string|int|float|bool|null $data - Data to change.
	 *
	 * @return object|array<int|string, mixed>|string|int|float|bool|null
	 */
	protected function replace_decoded( $data ) {
		if ( null === $data ) {
			return null;
		}

		if ( \is_int( $data ) || \is_float( $data ) ) {
			if ( \is_numeric( $this->old ) ) {
				return $this->replace( (string) $data );
			}
			return $data;
		}

		if ( \is_string( $data ) ) {
			return $this->replace( $data );
		}

		if ( ! \is_array( $data ) && ! \is_object( $data ) ) {
			return $data;
		}

		// @phpstan-ignore-next-line -- Classes are iterables but have no conditions to check.
		foreach ( $data as $key => $item ) {
			$updated_key = '';
			if ( \is_string( $key ) ) {
				$updated_key = $this->replace( $key );
			}
			// The key was updated.
			if ( '' !== $updated_key && $updated_key !== $key ) {
				if ( \is_array( $data ) ) {
					$data[ $updated_key ] = $this->replace_decoded( $item );
					unset( $data[ $key ] );
				} else {
					$data->{$updated_key} = $this->replace_decoded( $item );
					unset( $data->{$key} );
				}
			} elseif ( \is_array( $data ) ) {
				$data[ $key ] = $this->replace_decoded( $item );
			} else {
				$data->{$key} = $this->replace_decoded( $item );
			}
		}

		return $data;
	}


	/**
	 * Replace occurrences of an old url with a new url
	 * within a string.
	 *
	 * Also replace occurrences of an old url formatted using
	 * all available updaters
	 *
	 * A value no replacement changed is returned byte for byte.
	 *
	 * @param string $mysql_value - Original value from the database.
	 *
	 * @return string
	 */
	protected function replace( string $mysql_value ): string {
		/**
		 * `maybe_serialize` wraps the data in a string if passing an already
		 * serialized item when calling functions like `add_option`.
		 */
		if ( is_serialized( $mysql_value ) ) {
			if ( $this->has_data_to_update( $mysql_value ) ) {
				return $this->rewrite( $mysql_value );
			}
			return $mysql_value;
		}

		$replaced = $mysql_value;
		// Runs for every matching value, so a filled cache is read without a call.
		$replacements = $this->replacements ?? $this->get_replacements();
		foreach ( $replacements as $replacement ) {
			$replaced = \str_replace( $replacement['old'], $replacement['new'], $replaced, $count );
			if ( $replacement['counted'] ) {
				$this->count += $count;
			}
		}

		if ( $replaced === $mysql_value ) {
			return $mysql_value;
		}
		return \trim( $replaced );
	}


	/**
	 * Do we have any urls to actually update?
	 *
	 * - Check the old URL as is.
	 * - Check the old URL formatted by any updater.
	 *
	 * @param string $mysql_value - Original value from the database.
	 *
	 * @return bool
	 */
	protected function has_data_to_update( $mysql_value ): bool {
		if ( ! is_serialized( $mysql_value ) ) {
			return false;
		}

		foreach ( $this->get_replacements() as $replacement ) {
			foreach ( $replacement['old'] as $old ) {
				if ( false !== \strpos( $mysql_value, $old ) ) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Get the old URL as is and as formatted by each updater.
	 *
	 * @return string[]
	 */
	protected function get_old_urls(): array {
		return \array_merge( ...\array_column( $this->get_replacements(), 'old' ) );
	}


	/**
	 * Get the old URL as is, then as formatted by each updater, paired
	 * with the new URL, computed once per run.
	 *
	 * `str_replace` runs a group's pairs in order, so consecutive pairs
	 * which are counted alike share a group.
	 *
	 * Updaters which leave the old URL as it is are left out, because
	 * replacing the old URL as is already covers them.
	 *
	 * @phpstan-return non-empty-list<REPLACEMENT>
	 * @return array<int, array<string, string[]|bool>>
	 */
	protected function get_replacements(): array {
		if ( \is_array( $this->replacements ) ) {
			return $this->replacements;
		}
		$replacements = [
			[
				'old'     => [ $this->old ],
				'new'     => [ $this->new ],
				'counted' => true,
			],
		];
		foreach ( Repo::instance()->get_updaters() as $updater ) {
			$formatted = $updater::get_formatted( $this->old, $this->new );
			if ( $formatted['old'] !== $this->old ) {
				$counted = ! $updater::is_appending_update( $this->old, $this->new );
				$last = \count( $replacements ) - 1;
				if ( $counted === $replacements[ $last ]['counted'] ) {
					$replacements[ $last ]['old'][] = $formatted['old'];
					$replacements[ $last ]['new'][] = $formatted['new'];
				} else {
					$replacements[] = [
						'old'     => [ $formatted['old'] ],
						'new'     => [ $formatted['new'] ],
						'counted' => $counted,
					];
				}
			}
		}
		$this->replacements = $replacements;

		return $this->replacements;
	}


	/**
	 * Read the updaters again on the next replacement.
	 *
	 * @return void
	 */
	public function reset_updater_urls(): void {
		$this->replacements = null;
	}


	/**
	 * Getter for current count.
	 *
	 * @since 6.1.0
	 *
	 * @return int
	 */
	public function get_count() {
		return $this->count;
	}


	/**
	 * Set the property to determine if we are
	 * doing a dry run for counts, or actually updating
	 * the database.
	 *
	 * @since 6.1.0
	 *
	 * @param bool $dry_run - Is this a dry run or not.
	 */
	public function set_dry_run( $dry_run ) {
		$this->dry_run = $dry_run;
	}
}
