<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Updaters\Repo;

/**
 * Make updates to the database.
 *
 */
class Updates {
	protected $old;

	protected $new;

	protected $tables;


	/**
	 * Updates constructor.
	 *
	 * @param string $old - Entered old URL.
	 * @param string $new - Entered new URL.
	 * @param        $tables
	 */
	public function __construct( $old, $new, $tables ) {
		$this->old = $old;
		$this->new = $new;
		$this->tables = $tables;
	}


	public function update_table_columns( $table ) {
		global $wpdb;
		$doubled = $this->get_doubled_up_subdomain();
		$columns = $wpdb->get_col( $wpdb->prepare( "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$wpdb->dbname}' AND TABLE_NAME=%s", $table ) );
		array_walk( $columns, function( $column ) use ( $table, $doubled ) {
			Database::instance()->update_column( $table, $column, $this->old, $this->new );
			$this->update_column_with_updaters( $table, $column );
			$this->update_email_addresses( $table, $column );

			if ( null !== $doubled ) {
				Database::instance()->update_column( $table, $column, $doubled, $this->new );
			}
		} );
	}


	protected function update_email_addresses( $table, $column ) {
		$url = wp_parse_url( $this->old );
		$doubled = $this->get_doubled_up_subdomain();
		if ( null === $doubled || ! empty( $url['scheme'] ) ) {
			return 0;
		}
		Database::instance()->update_column( $table, $column, '@' . $this->new, '@' . $this->old );
	}


	protected function update_column_with_updaters( $table, $column ) {
		$doubled = $this->get_doubled_up_subdomain();
		array_map( function ( $class ) use ( $doubled, $table, $column ) {
			if ( class_exists( $class ) ) {
				$updater = $class::factory( $table, $column, $this->old, $this->new );
				$updater->update_data();
				if ( null !== $doubled ) {
					$updater = new $class( $table, $column, $doubled, $this->new );
					$updater->update_data();
				}
			}
		}, Repo::instance()->get_updaters() );
	}


	public function update_serialized_values() {
		$serialized = new Serialized( $this->old, $this->new );
		$serialized->update_all_serialized_tables( $this->tables );

		$doubled = $this->get_doubled_up_subdomain();
		if ( null !== $doubled ) {
			$serialized = new Serialized( $doubled, $this->new );
			$serialized->update_all_serialized_tables( $this->tables );
			// Handle emails.
			$serialized = new Serialized( '@' . $this->new, '@' . $this->old );
			$serialized->update_all_serialized_tables( $this->tables );
		}
	}


	/**
	 * If the new domain is the old one with a new sub-domain like www.
	 * the first round of updates will create double sub-domains in
	 * the database like www.www.
	 *
	 * If we doubled up some domains we get the result, or null
	 * if the entered values would not create doubles.
	 *
	 * @since 6.1.0
	 *
	 * @return string|null
	 */
	protected function get_doubled_up_subdomain() {
		if ( strpos( $this->new, $this->old ) !== false ) {
			list( $subdomain ) = explode( '.', $this->new );
			return $subdomain . '.' . $this->new;
		}
		return null;
	}


	/**
	 * @param string $old_url - Entered old URL.
	 * @param string $new_url - Entered new URL.
	 *
	 * @param        $tables
	 *
	 * @return static
	 */
	public static function factory( $old_url, $new_url, $tables ) {
		return new static( $old_url, $new_url, $tables );
	}
}
