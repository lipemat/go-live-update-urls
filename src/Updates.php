<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Updaters\Repo;

/**
 * Translated provided URLS into various steps to update the database.
 *
 * While no updates to the database are done within this class,
 * all calls to the methods, which update the database go through here
 * except for serialized data.
 *
 * This class determines, which data needs to be updated in which way
 * and makes necessary calls.
 *
 * @since 6.1.0
 */
class Updates {
	/**
	 * Entered OLD URL.
	 *
	 * @var string
	 */
	protected $old_url;

	/**
	 * Entered OLD URL.
	 *
	 * @var string
	 */
	protected $new_url;

	/**
	 * List of selected tables.
	 *
	 * @var string[]
	 */
	protected $tables;


	/**
	 * Updates constructor.
	 *
	 * @param string   $old    - Entered old URL.
	 * @param string   $new    - Entered new URL.
	 * @param string[] $tables - List of tables to interact with.
	 */
	final public function __construct( $old, $new, array $tables ) {
		$this->old_url = $old;
		$this->new_url = $new;
		$this->tables = $tables;
	}


	/**
	 * Update all instances of the URLS within a provided table.
	 *
	 * Takes care of all calls related to necessary updates.
	 *
	 * @param string $table - Table to update.
	 *
	 * @return int
	 */
	public function update_table_columns( $table ) {
		$doubled = $this->get_doubled_up_subdomain();
		$columns = $this->get_table_columns( $table );
		$count = 0;
		\array_walk( $columns, function( $column ) use ( $table, $doubled, &$count ) {
			$count += (int) Database::instance()->update_column( $table, $column, $this->old_url, $this->new_url );
			$count += (int) $this->update_column_with_updaters( $table, $column );
			$this->update_email_addresses( $table, $column );

			if ( null !== $doubled ) {
				$count -= (int) Database::instance()->update_column( $table, $column, $doubled, $this->new_url );
			}
		} );

		return $count;
	}


	/**
	 * Counts all instances of the URLS within a provided table.
	 *
	 * @param string $table - Table to count.
	 *
	 * @return int
	 */
	public function count_table_urls( $table ) {
		$doubled = $this->get_doubled_up_subdomain();
		$columns = $this->get_table_columns( $table );
		$count = 0;
		\array_walk( $columns, function( $column ) use ( $table, $doubled, &$count ) {
			$count += (int) Database::instance()->count_column_urls( $table, $column, $this->old_url );
			$count += (int) $this->count_column_urls_with_updaters( $table, $column );

			if ( null !== $doubled ) {
				$count -= (int) Database::instance()->count_column_urls( $table, $column, $this->new_url );
			}
		} );

		return $count;
	}


	/**
	 * Remove any prepended subdomain from email addresses.
	 *
	 * If we change a domain to a subdomain like www, and an email address
	 * is using the original domain we end up with an email address that
	 * includes @www We remove the prepended www from email addresses
	 * here.
	 *
	 * @param string $table  - Any database table.
	 * @param string $column - Any column within the provided table.
	 *
	 * @return int
	 */
	protected function update_email_addresses( $table, $column ) {
		$url = wp_parse_url( $this->old_url );
		$doubled = $this->get_doubled_up_subdomain();
		if ( null === $doubled || ! empty( $url['scheme'] ) ) {
			return 0;
		}
		return Database::instance()->update_column( $table, $column, '@' . $this->new_url, '@' . $this->old_url );
	}


	/**
	 * Using all registered updaters, replace the Updater's variation
	 * of the URL.
	 *
	 * Actual translation and updating is handled by each updater.
	 * We simply load and call them here.
	 *
	 * @param string $table  - Any database table.
	 * @param string $column - Any column within the provided table.
	 *
	 * @return int
	 */
	protected function update_column_with_updaters( $table, $column ) {
		$doubled = $this->get_doubled_up_subdomain();
		$count = 0;
		array_map( function ( $class ) use ( $doubled, $table, $column, &$count ) {
			if ( class_exists( $class ) ) {
				$updater = $class::factory( $table, $column, $this->old_url, $this->new_url );
				$count += (int) $updater->update_data();
				if ( null !== $doubled ) {
					$updater = $class::factory( $table, $column, $doubled, $this->new_url );
					$updater->update_data();
				}
			}
		}, Repo::instance()->get_updaters() );
		return $count;
	}


	/**
	 * Using all registered updaters, count the Updater's variation
	 * of the URL.
	 *
	 * Actual counting is handled by each updater.
	 * We simply load and call them here.
	 *
	 * @param string $table  - Any database table.
	 * @param string $column - Any column within the provided table.
	 *
	 * @return int
	 */
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


	/**
	 * Update values in all serialized columns within the specified tables.
	 *
	 * Detection of which columns are possibly serialized is handled within
	 * the Serialized class. We simply provide the OLD and NEW URL and the
	 * list of tables we are updating.
	 *
	 * @return int[]
	 */
	public function update_serialized_values() {
		$serialized = new Serialized( $this->old_url, $this->new_url );
		$counts = $serialized->update_all_serialized_tables( $this->tables );

		$doubled = $this->get_doubled_up_subdomain();
		if ( null !== $doubled ) {
			$serialized = new Serialized( $doubled, $this->new_url );
			$counts = array_combine( array_keys( $counts ), array_map( function ( $value, $subtract ) {
				return $value - $subtract;
			}, $counts, $serialized->update_all_serialized_tables( $this->tables ) ) );

			// Remove an prepended subdomain like www. from email addresses.
			$serialized = new Serialized( '@' . $this->new_url, '@' . $this->old_url );
			$counts = array_combine( array_keys( $counts ), array_map( function ( $value, $subtract ) {
				return $value - $subtract;
			}, $counts, $serialized->update_all_serialized_tables( $this->tables ) ) );
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
	public function get_doubled_up_subdomain() {
		if ( static::is_subdomain( $this->old_url, $this->new_url ) ) {
			return str_replace( $this->old_url, $this->new_url, $this->new_url );
		}
		return null;
	}


	/**
	 * Return all database columns for a specified table that
	 * match the column types we update.
	 *
	 * We include any varchar or char which are 21 characters
	 * or above which takes care of a lot of core columns which
	 * don't store Urls.
	 *
	 * @param string $table - Database table to retrieve from.
	 *
	 * @since 6.1.0
	 *
	 * @return string[]
	 */
	protected function get_table_columns( $table ) {
		global $wpdb;

		$all = $wpdb->get_results( $wpdb->prepare( "SELECT COLUMN_NAME as name, COLUMN_TYPE as type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$wpdb->dbname}' AND TABLE_NAME=%s", $table ) );
		$types = Database::instance()->get_column_types();

		return wp_list_pluck( array_filter( $all, function ( $column ) use ( $types ) {
			// Strip the (\d) from varchar and char with (21) and over.
			return in_array( preg_replace( '/\((\d{3}|[3-9][\d]|[2][1-9])[\d]*?\)/', '', $column->type ), $types, true );
		} ), 'name' );
	}


	/**
	 * Is a new URL a subdomain of the old URL?
	 *
	 * @param string $old_url - Old URL.
	 * @param string $new_url - New URL.
	 *
	 * @since 6.2.4
	 *
	 * @return bool
	 */
	public static function is_subdomain( $old_url, $new_url ) {
		return false !== strpos( $new_url, $old_url );
	}


	/**
	 * Construct the Updates class.
	 *
	 * @param string   $old_url - Entered old URL.
	 * @param string   $new_url - Entered new URL.
	 *
	 * @param string[] $tables  - List of tables to interact with.
	 *
	 * @return static
	 */
	public static function factory( $old_url, $new_url, array $tables ) {
		return new static( $old_url, $new_url, $tables );
	}
}
