<?php
/* @noinspection UnserializeExploitsInspection */

//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
//phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Updaters\Repo;
use Go_Live_Update_Urls\Updaters\Updaters_Abstract;

/**
 * Serialized data handling.
 *
 * @author OnPoint Plugins
 * @since  6.0.0
 */
class Serialized {
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
	 * Serialized constructor.
	 *
	 * @param string $old_url - Old URL.
	 * @param string $new_url - New URL.
	 */
	public function __construct( $old_url, $new_url ) {
		$this->new = $new_url;
		$this->old = $old_url;
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
		$column = esc_sql( $column );
		$table = esc_sql( $table );
		$pk = $wpdb->get_results( 'SHOW KEYS FROM `' . $table . "` WHERE Key_name = 'PRIMARY'" );
		if ( empty( $pk[0] ) ) {
			$pk = $wpdb->get_results( 'SHOW KEYS FROM `' . $table . '`' );
			if ( empty( $pk[0] ) ) {
				return 0;    // Fail.
			}
		}
		$primary_key_column = $pk[0]->Column_name;
		Skip_Rows::instance()->set_current_table( $table, $primary_key_column );

		// Get all serialized rows.
		$rows = $wpdb->get_results( "SELECT `$primary_key_column`, `{$column}` FROM `{$table}` WHERE `{$column}` LIKE 'a:%' OR `{$column}` LIKE 'O:%' OR `{$column}` LIKE 's:%';" );

		foreach ( $rows as $row ) {
			if ( ! $this->has_data_to_update( $row->{$column} ) ) {
				continue;
			}

			Skip_Rows::instance()->set_current_row_id( $row->{$primary_key_column} );
			$clean = $this->replace_tree( @unserialize( $row->{$column} ) );
			if ( empty( $clean ) ) {
				continue;
			}

			if ( ! $this->dry_run ) {
				$clean = @serialize( $clean );
				if ( '' !== $clean ) {
					$wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET `{$column}`=%s WHERE `{$primary_key_column}` = %s", $clean, $row->{$primary_key_column} ) );
				}
			}
		}

		return $this->count;
	}


	/**
	 * Replaces all the occurrences of a string in a multidimensional array or Object
	 *
	 * @noinspection OffsetOperationsInspection
	 *
	 * @since 5.2.0
	 *
	 * @param object|array|string|int|float|null $data - Data to change.
	 *
	 * @return object|array|string|int|float|null
	 */
	public function replace_tree( $data ) {
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

		if ( $this->has_missing_classes( $data ) ) {
			Skip_Rows::instance()->skip_current();
			return $data;
		}

		// @phpstan-ignore-next-line -- Sanity check.
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
					$data[ $updated_key ] = $this->replace_tree( $item );
					unset( $data[ $key ] );
				} else {
					$data->{$updated_key} = $this->replace_tree( $item );
					unset( $data->{$key} );
				}
			} elseif ( \is_array( $data ) ) {
				$data[ $key ] = $this->replace_tree( $item );
			} else {
				$data->{$key} = $this->replace_tree( $item );
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
			$result = @unserialize( $mysql_value );
			if ( false === $result ) {
				return $mysql_value;
			}
			return @serialize( $this->replace_tree( $result ) );
		}

		$mysql_value = \str_replace( $this->old, $this->new, $mysql_value, $count );
		$this->count += $count;

		foreach ( Repo::instance()->get_updaters() as $updater ) {
			/* @var Updaters_Abstract $updater - Updater class instance. */
			$formatted = $updater::get_formatted( $this->old, $this->new );
			if ( $formatted['old'] !== $this->old ) {
				$mysql_value = \str_replace( $formatted['old'], $formatted['new'], $mysql_value, $updater_count );
				if ( ! $updater::is_appending_update( $this->old, $this->new ) ) {
					$this->count += $updater_count;
				}
			}
		}

		return \trim( $mysql_value );
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

		if ( false !== strpos( $mysql_value, $this->old ) ) {
			return true;
		}

		foreach ( Repo::instance()->get_updaters() as $_updater ) {
			/* @var Updaters_Abstract $_updater - Updater class instance. */
			$formatted = $_updater::get_formatted( $this->old, $this->new );
			if ( false !== strpos( $mysql_value, $formatted['old'] ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Wrapper around `unserialize` to support gracefully
	 * failing to unserialize a value due to a missing class.
	 *
	 * If a class is not available when `unserialize` is called
	 * PHP automatically converts the result to `__PHP_Incomplete_Class`.
	 *
	 * @ticket #10723
	 *
	 * @since 6.5.0
	 *
	 * @param object|array $data - Value from the database column.
	 *
	 * @return bool
	 */
	protected function has_missing_classes( $data ) {
		if ( ! \is_array( $data ) && is_a( $data, \__PHP_Incomplete_Class::class ) ) {
			// Hack to get the name of the class from __PHP_Incomplete_Class without `Error`.
			foreach ( (array) $data as $key => $name ) {
				if ( '__PHP_Incomplete_Class_Name' === $key ) {
					Skip_Rows::instance()->log_error( $name );
					return true;
				}
			}
			return true;
		}
		return false;
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
