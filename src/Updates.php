<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Updaters\Repo;

/**
 * Make various updates to the database.
 *
 */
class Updates {
	protected $old_url;

	protected $new_url;

	protected $tables;


	/**
	 * Updates constructor.
	 *
	 * @param string $old - Entered old URL.
	 * @param string $new - Entered new URL.
	 * @param        $tables
	 */
	public function __construct( $old, $new, $tables ) {
		$this->old_url = $old;
		$this->new_url = $new;
		$this->tables = $tables;
	}


	public function update_table_columns( $table ) {
		$doubled = $this->get_doubled_up_subdomain();
		$columns = $this->get_table_columns( $table );
		$count = 0;
		array_walk( $columns, function( $column ) use ( $table, $doubled, &$count ) {
			$count += (int) Database::instance()->update_column( $table, $column, $this->old_url, $this->new_url );
			$count += (int) $this->update_column_with_updaters( $table, $column );
			$this->update_email_addresses( $table, $column );

			if ( null !== $doubled ) {
				Database::instance()->update_column( $table, $column, $doubled, $this->new_url );
			}
		} );

		return $count;
	}


	public function count_table_urls( $table ) {
		$columns = $this->get_table_columns( $table );
		$count = 0;
		array_walk( $columns, function ( $column ) use ( $table, &$count ) {
			$count += (int) Database::instance()->count_column_urls( $table, $column, $this->old_url );
			$count += (int) $this->count_column_urls_with_updaters( $table, $column );
		} );

		return $count;
	}


	protected function update_email_addresses( $table, $column ) {
		$url = wp_parse_url( $this->old_url );
		$doubled = $this->get_doubled_up_subdomain();
		if ( null === $doubled || ! empty( $url['scheme'] ) ) {
			return 0;
		}
		Database::instance()->update_column( $table, $column, '@' . $this->new_url, '@' . $this->old_url );
	}


	protected function update_column_with_updaters( $table, $column ) {
		$doubled = $this->get_doubled_up_subdomain();
		$count = 0;
		array_map( function ( $class ) use ( $doubled, $table, $column, &$count ) {
			if ( class_exists( $class ) ) {
				$updater = $class::factory( $table, $column, $this->old_url, $this->new_url );
				$count += (int) $updater->update_data();
				if ( null !== $doubled ) {
					$updater = new $class( $table, $column, $doubled, $this->new_url );
					$updater->update_data();
				}
			}
		}, Repo::instance()->get_updaters() );
		return $count;
	}


	protected function count_column_urls_with_updaters( $table, $column ) {
		$count = 0;
		array_map( function ( $class ) use ( $table, $column, &$count ) {
			if ( class_exists( $class ) ) {
				$updater = $class::factory( $table, $column, $this->old_url, $this->new_url );
				$count += (int) $updater->count_urls();
			}
		}, Repo::instance()->get_updaters() );
		return $count;
	}


	public function update_serialized_values() {
		$serialized = new Serialized( $this->old_url, $this->new_url );
		$counts = $serialized->update_all_serialized_tables( $this->tables );

		$doubled = $this->get_doubled_up_subdomain();
		if ( null !== $doubled ) {
			$serialized = new Serialized( $doubled, $this->new_url );
			$serialized->update_all_serialized_tables( $this->tables );
			// Handle emails.
			$serialized = new Serialized( '@' . $this->new_url, '@' . $this->old_url );
			$serialized->update_all_serialized_tables( $this->tables );
		}

		return $counts;
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
		if ( strpos( $this->new_url, $this->old_url ) !== false ) {
			list( $subdomain ) = explode( '.', $this->new_url );
			return $subdomain . '.' . $this->new_url;
		}
		return null;
	}


	/**
	 * Return all database columns for a specified table.
	 *
	 * @param string $table - Database table to retrieve from.
	 *
	 * @since 6.1.0
	 *
	 * @return string[]
	 */
	protected function get_table_columns( $table ) {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$wpdb->dbname}' AND TABLE_NAME=%s", $table ) );
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
