<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Updaters\Repo;

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
	 * Serialized constructor.
	 *
	 * @param string $old - Old URL.
	 * @param string $new - New URL.
	 */
	public function __construct( $old, $new ) {
		$this->new = $new;
		$this->old = $old;
	}


	/**
	 * Go through every registered serialized table and update them one by one
	 *
	 * @param array $tables - The tables to update.
	 *
	 * @since 5.2.5 - Only update provided tables.
	 *
	 * @return void
	 */
	public function update_all_serialized_tables( array $tables ) {
		$serialized_tables = Database::instance()->get_serialized_tables();

		foreach ( $serialized_tables as $table => $columns ) {
			if ( ! in_array( $table, $tables, true ) ) {
				continue;
			}
			foreach ( (array) $columns as $_column ) {
				$this->update_table( $table, $_column );
			}
		}
	}


	/**
	 * Query all serialized rows from a database table and update them one by one
	 *
	 * @param string $table - Database table.
	 * @param string $column - Database column.
	 *
	 * @return void
	 */
	protected function update_table( $table, $column ) {
		global $wpdb;
		$column = (string) esc_sql( $column );
		$table = (string) esc_sql( $table );
		$pk = $wpdb->get_results( 'SHOW KEYS FROM `' . $table . "` WHERE Key_name = 'PRIMARY'" );
		if ( empty( $pk[0] ) ) {
			$pk = $wpdb->get_results( 'SHOW KEYS FROM `' . $table . '`' );
			if ( empty( $pk[0] ) ) {
				// Fail.
				return;
			}
		}
		$primary_key_column = $pk[0]->Column_name;

		// Get all serialized rows.
		$rows = $wpdb->get_results( "SELECT $primary_key_column, {$column} FROM {$table} WHERE {$column} LIKE 'a:%' OR {$column} LIKE 'O:%'" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( $rows as $row ) {
			if ( ! $this->has_data_to_update( $row->{$column} ) ) {
				continue;
			}

			//phpcs:disable
			$clean = $this->replace_tree( @unserialize( $row->{$column} ) );
			$clean = @serialize( $clean );
			//phpcs:enable

			$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET {$column}=%s WHERE {$primary_key_column}=%s", $clean, $row->{$primary_key_column} ) ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}


	/**
	 * Replaces all the occurrences of a string in a multi-dimensional array or Object
	 *
	 * @param iterable|string $data - Data to change.
	 *
	 * @uses  itself to call each level of the array
	 *
	 * @since 5.2.0
	 *
	 * @return mixed
	 */
	public function replace_tree( $data ) {
		if ( is_string( $data ) ) {
			return $this->replace( $data );
		}

		if ( ! is_array( $data ) && ! is_object( $data ) ) {
			return $data;
		}

		foreach ( $data as $key => $item ) {
			if ( is_array( $data ) ) {
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
	protected function replace( $mysql_value ) {
		foreach ( Repo::instance()->get_updaters() as $_updater ) {
			$mysql_value = str_replace( $_updater::apply_rule_to_url( $this->old ), $_updater::apply_rule_to_url( $this->new ), $mysql_value );
		}

		return trim( str_replace( $this->old, $this->new, $mysql_value ) );
	}


	/**
	 * Do we have any urls to actually update?
	 * Check first for serialized data,
	 * Then check for occurrences of any urls formatted by an updater
	 *
	 * @param string $mysql_value - Original value from the database.
	 *
	 * @return bool
	 */
	protected function has_data_to_update( $mysql_value ) {
		if ( ! is_serialized( $mysql_value ) ) {
			return false;
		}

		if ( strpos( $mysql_value, $this->old ) !== false ) {
			return true;
		}

		foreach ( Repo::instance()->get_updaters() as $_updater ) {
			if ( strpos( $mysql_value, $_updater::apply_rule_to_url( $this->old ) ) !== false ) {
				return true;
			}
		}

		return false;
	}

}
