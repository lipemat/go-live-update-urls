<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Traits\Singleton;

/**
 * Database manipulation.
 *
 * @author OnPoint Plugins
 * @since  6.0.0
 */
class Database {
	use Singleton;

	/**
	 * Get list of tables we treat as serialized when updating
	 *
	 * @since   5.0.0
	 *
	 * @return array( %table_name% => %table_column% )
	 */
	public function get_serialized_tables() {
		global $wpdb;
		// Default tables with serialized data.
		$serialized_tables = [
			$wpdb->options     => 'option_value',
			$wpdb->postmeta    => 'meta_value',
			$wpdb->commentmeta => 'meta_value',
			$wpdb->signups     => 'meta',
			$wpdb->termmeta    => 'meta_value',
			$wpdb->usermeta    => 'meta_value',
		];

		// We are not going to update site meta if we are not on main blog.
		if ( is_multisite() ) {
			$serialized_tables[ $wpdb->sitemeta ] = 'meta_value';
			// WP 5.0.0+.
			if ( isset( $wpdb->blogmeta ) ) {
				$serialized_tables[ $wpdb->blogmeta ] = 'meta_value';
			}
		}

		return apply_filters( 'go-live-update-urls/database/serialized-tables', $serialized_tables );
	}


	/**
	 * Get the list of tables that were not create by WP core
	 *
	 * @return array
	 */
	public function get_custom_plugin_tables() {
		$core_tables = $this->get_core_tables();
		$all_tables = $this->get_all_table_names();
		$all_tables = array_flip( $all_tables );
		foreach ( $core_tables as $_table ) {
			unset( $all_tables[ $_table ] );
		}

		return apply_filters( 'go-live-update-urls/database/plugin-tables', array_keys( $all_tables ) );
	}


	/**
	 * Get the list of WP core tables
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function get_core_tables() {
		global $wpdb;

		$tables = [
			$wpdb->posts,
			$wpdb->comments,
			$wpdb->links,
			$wpdb->options,
			$wpdb->postmeta,
			$wpdb->terms,
			$wpdb->term_taxonomy,
			$wpdb->term_relationships,
			$wpdb->termmeta,
			$wpdb->commentmeta,
			$wpdb->users,
			$wpdb->usermeta,
		];

		if ( is_multisite() ) {
			$tables[] = $wpdb->blogs;
			$tables[] = $wpdb->signups;
			$tables[] = $wpdb->site;
			$tables[] = $wpdb->sitemeta;
			$tables[] = $wpdb->sitecategories;
			$tables[] = $wpdb->registration_log;
			// WP 5.0.0+.
			if ( isset( $wpdb->blogmeta ) ) {
				$tables[] = $wpdb->blogmeta;
			}
		}

		return apply_filters( 'go-live-update-urls/database/core-tables', $tables );
	}


	/**
	 * Get types of MySQL fields which may contain URLS.
	 *
	 * Only fields of these types will be updated.
	 *
	 * @since 6.1.0
	 *
	 * @return array
	 */
	public function get_column_types() {
		$types = [
			'char',
			'longtext',
			'longtext',
			'mediumtext',
			'text',
			'tinytext',
			'varchar',
		];
		return apply_filters( 'go-live-update-urls/database/column-types', $types, $this );
	}


	/**
	 * Get the names of every table in this blog
	 * If we are multisite, we also get the global tables.
	 *
	 * @since 5.0.1
	 *
	 * @return array
	 */
	public function get_all_table_names() {
		global $wpdb;
		$query = "SELECT TABLE_NAME as TableName FROM information_schema.TABLES WHERE TABLE_SCHEMA='" . $wpdb->dbname . "' AND TABLE_NAME LIKE '" . $wpdb->esc_like( $wpdb->prefix ) . "%'";

		// Site 1's 'LIKE wp_%' will return all tables in the database,
		// so we exclude all possible sub sites (e.g., wp_2, wp_3 up to 9).
		$not_like = null;
		if ( 1 === (int) $wpdb->blogid && is_multisite() ) {
			for ( $i = 1; $i <= 9; $i ++ ) {
				$not_like .= $wpdb->prepare( '%s,', $wpdb->prefix . $i );
			}
			$query .= ' AND SUBSTRING(TABLE_NAME,1,4) NOT IN (' . substr( $not_like, 0, - 1 ) . ')';
		}
		return $wpdb->get_col( $query );
	}


	/**
	 * Make the actual changes to the database
	 *
	 * @param string $old_url - the old URL.
	 * @param string $new_url - the new URL.
	 * @param array  $tables  - the tables we are going to update.
	 *
	 * @since 5.0.0
	 *
	 * @return int[]
	 */
	public function update_the_database( $old_url, $new_url, array $tables ) {
		do_action( 'go-live-update-urls/database/before-update', $old_url, $new_url, $tables, $this );
		$tables = apply_filters( 'go-live-update-urls/database/update-tables', $tables, $this );

		$updates = Updates::factory( $old_url, $new_url, $tables );
		$counts = $updates->update_serialized_values();
		foreach ( (array) $tables as $_table ) {
			if ( ! array_key_exists( $_table, $counts ) ) {
				$counts[ $_table ] = 0;
			}
			$counts[ $_table ] += $updates->update_table_columns( $_table );
		}

		$counts = apply_filters( 'go-live-update-urls/database/updated/counts', $counts, $old_url, $new_url, $tables, $this );

		do_action( 'go-live-update-urls/database/after-update', $old_url, $new_url, $tables, $this );
		return $counts;
	}


	/**
	 * Count all occurrences of the old URL within a provided
	 * list of tables.
	 *
	 * @param string $old_url - the old URL.
	 * @param string $new_url - the new URL.
	 * @param array  $tables  - the tables we are going to update.
	 *
	 * @since 5.0.0
	 *
	 * @return int[]
	 */
	public function count_database_urls( $old_url, $new_url, array $tables ) {
		do_action( 'go-live-update-urls/database/before-counting', $old_url, $new_url, $tables, $this );
		$tables = apply_filters( 'go-live-update-urls/database/update-tables', $tables, $this );

		$updates = Updates::factory( $old_url, $new_url, $tables );
		$counts = [];
		foreach ( (array) $tables as $_table ) {
			$counts[ $_table ] = $updates->count_table_urls( $_table );
		}

		$counts = apply_filters( 'go-live-update-urls/database/counted/counts', $counts, $old_url, $new_url, $tables, $this );

		do_action( 'go-live-update-urls/database/after-counting', $old_url, $new_url, $tables, $this );

		return $counts;
	}


	/**
	 * Update an individual table's column.
	 *
	 * @param string $table   -  Table to update.
	 * @param string $column  - Column to update.
	 * @param string $old_url - Old URL.
	 * @param string $new_url - New URL.
	 *
	 * @since 5.3.0
	 *
	 * @return int
	 */
	public function update_column( $table, $column, $old_url, $new_url ) {
		global $wpdb;

		$count = $this->count_column_urls( $table, $column, $old_url );
		$update_query = 'UPDATE ' . $table . ' SET `' . $column . '` = replace(`' . $column . '`, %s, %s)';

		if ( $this->supports_skipping( $table ) ) {
			$skip = esc_sql( implode( ',', (array) Skip_Rows::instance()->get_skipped( $table ) ) );
			$primary = esc_sql( Skip_Rows::instance()->get_primary_key( $table ) );
			$update_query .= " WHERE `{$primary}` NOT IN ({$skip})";
		}

		$wpdb->query( $wpdb->prepare( $update_query, [ $old_url, $new_url ] ) );
		return $count;
	}


	/**
	 * Count of number of rows in a table which contain the old URL.
	 *
	 * When updating, the serialized data is updated first and this
	 * counts the left overs.
	 *
	 * When dry-run counting, this will count all occurrences in the
	 * database.
	 *
	 * @param string $table   - Table to update.
	 * @param string $column  - Column to update.
	 * @param string $old_url - Old URL.
	 *
	 * @since 6.1.0
	 *
	 * @return int
	 */
	public function count_column_urls( $table, $column, $old_url ) {
		global $wpdb;

		$query = "SELECT SUM( ROUND( ( LENGTH( `${column}` ) - LENGTH( REPLACE( `${column}`, %s, '' ) ) ) / LENGTH( %s ) ) ) from `${table}`";

		return (int) $wpdb->get_var( $wpdb->prepare( $query, [ $old_url, $old_url ] ) );
	}


	/**
	 * Does this table support skipping rows?
	 *
	 * 1. Does it have rows to skip?
	 * 2. Does the filter allow skipping?
	 *
	 * @since 6.5.0
	 *
	 * @param string $table - Database table.
	 *
	 * @return bool
	 */
	protected function supports_skipping( $table ) {
		if ( empty( Skip_Rows::instance()->get_skipped( $table ) ) || null === Skip_Rows::instance()->get_primary_key( $table ) ) {
			return false;
		}

		return (bool) apply_filters( 'go-live-update-urls-pro/database/supports-skipping', true, $table, $this );
	}
}
