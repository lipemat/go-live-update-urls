<?php

/**
 * @author Mat Lipe
 * @since  5.2.0
 *
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


	public function update() {
		$serialized_tables = Go_Live_Update_Urls_Database::instance()->get_serialized_tables();

		foreach ( $serialized_tables as $table => $columns ) {
			foreach ( (array) $columns as $_column ) {
				$this->update_serialized_table( $table, $_column );
			}
		}
	}


	protected function update_serialized_table( $table, $column ) {
		global $wpdb;
		$pk = $wpdb->get_results( "SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'" );
		if ( empty( $pk[0] ) ) {
			$pk = $wpdb->get_results( "SHOW KEYS FROM {$table}" );
			if ( empty( $pk[0] ) ) {
				//fail
				return;
			}
		}

		$primary_key_column = $pk[0]->Column_name;

		//Get all the Serialized Rows and Replace them properly
		$rows = $wpdb->get_results( "SELECT $primary_key_column, {$column} FROM {$table} WHERE {$column} LIKE 'a:%' OR {$column} LIKE 'O:%'" );

		foreach ( $rows as $row ) {
			if ( ! is_serialized( $row->{$column} ) ) {
				continue;
			}
			if ( strpos( $row->{$column}, $this->old ) === false ) {
				continue;
			}

			$data = @unserialize( $row->{$column} );

			$clean = $this->replace_tree( $data );

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
	 *
	 */
	public function replace_tree( $data ) {
		if ( is_string( $data ) ) {
			return trim( str_replace( $this->old, $this->new, $data ) );
		}

		if ( ! is_array( $data ) && ! is_object( $data ) ) {
			return $data;
		}

		foreach ( $data as $key => $item ) {
			if ( is_array( $data ) ) {
				$data[ $key ] = $this->replace_tree( $item, $this->old, $this->new );
			} else {
				$data->{$key} = $this->replace_tree( $item, $this->old, $this->new );
			}
		}

		return $data;
	}


}
