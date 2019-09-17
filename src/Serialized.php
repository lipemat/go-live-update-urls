<?php

/**
 * @author OnPoint Plugins
 * @since  5.2.0
 */
class Go_Live_Update_Urls_Serialized {
	protected $column;
	protected $new;
	protected $old;
	protected $table;


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
		$serialized_tables = Go_Live_Update_Urls_Database::instance()->get_serialized_tables();

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
	 * @param $table
	 * @param $column
	 *
	 * @return void
	 */
	protected function update_table( $table, $column ) {
		global $wpdb;
		$pk = $wpdb->get_results( "SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'" );
		if ( empty( $pk[0] ) ) {
			$pk = $wpdb->get_results( "SHOW KEYS FROM {$table}" );
			if ( empty( $pk[0] ) ) {
				// fail
				return;
			}
		}

		$primary_key_column = $pk[0]->Column_name;

		// Get all the Serialized Rows and Replace them properly
		$rows = $wpdb->get_results( "SELECT $primary_key_column, {$column} FROM {$table} WHERE {$column} LIKE 'a:%' OR {$column} LIKE 'O:%'" );

		foreach ( $rows as $row ) {
			// skip the overhead of updating things that have nothing to update
			if ( ! $this->has_data_to_update( $row->{$column} ) ) {
				continue;
			}

			$clean = $this->replace_tree( @unserialize( $row->{$column} ) );
			$clean = @serialize( $clean );

			$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET {$column}=%s WHERE {$primary_key_column}=%s", $clean, $row->{$primary_key_column} ) );

		}
	}


	/**
	 * Replaces all the occurrences of a string in a multi-dimensional array or Object
	 *
	 * @uses  itself to call each level of the array
	 *
	 * @param iterable|string $data to change
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
	 * @param string $data
	 *
	 * @return string
	 */
	protected function replace( $data ) {
		foreach ( $this->get_updater_objects() as $_updater ) {
			/** @var Go_Live_Update_Urls__Updaters__Abstract $_updater */
			$data = str_replace( $_updater->apply_rule_to_url( $this->old ), $_updater->apply_rule_to_url( $this->new ), $data );
		}

		return trim( str_replace( $this->old, $this->new, $data ) );
	}


	/**
	 * Do we have any urls to actually update?
	 * Check first for serialized data,
	 * Then check for occurrences of any urls formatted by an updater
	 *
	 * @param string $data
	 *
	 * @return bool
	 */
	protected function has_data_to_update( $data ) {
		if ( ! is_serialized( $data ) ) {
			return false;
		}

		if ( strpos( $data, $this->old ) !== false ) {
			return true;
		}

		foreach ( $this->get_updater_objects() as $_updater ) {
			if ( strpos( $data, $_updater->apply_rule_to_url( $this->old ) ) !== false ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Get an array of all available url updaters
	 *
	 * @todo The current updater architecture uses object with 4 arguments passed to the class
	 *       Come up with a better architecture which gives us access to apply_url_to_url() without
	 *       empty constructing an object.
	 *
	 * @return Go_Live_Update_Urls__Updaters__Abstract[]
	 */
	protected function get_updater_objects() {
		static $updaters;
		if ( null === $updaters ) {
			$updaters = (array) Go_Live_Update_Urls__Updaters__Repo::instance()->get_updaters();
			foreach ( $updaters as $k => $_class ) {
				$updaters[ $k ] = new $_class( null, null, null, null );
			}
		}

		return $updaters;
	}

}
