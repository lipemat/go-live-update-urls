<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Traits\Singleton;
use Go_Live_Update_Urls\Updaters\Repo;
use Go_Live_Update_Urls\Updaters\Updaters_Abstract;

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
	 * Get the names of every table in this blog
	 * If we are multisite, we also get the global tables
	 *
	 * @since 5.0.1
	 *
	 * @return array
	 */
	public function get_all_table_names() {
		global $wpdb;
		$query = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='" . $wpdb->dbname . "' AND TABLE_NAME LIKE '" . $wpdb->prefix . "%'";

		// Site 1's 'LIKE wp_%' will return all tables in the database
		// so we exclude all possible sub sites e.g. wp_2, wp_3 up to 9.
		$not_like = null;
		if ( 1 === (int) $wpdb->blogid && is_multisite() ) {
			for ( $i = 1; $i <= 9; $i ++ ) {
				$not_like .= "'" . $wpdb->prefix . $i . "',";
			}
			$not_like = substr( $not_like, 0, - 1 );
			$query .= ' AND SUBSTRING(TABLE_NAME,1,4) NOT IN (' . $not_like . ')';
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
	 * @return bool
	 */
	public function update_the_database( $old_url, $new_url, array $tables ) {
		global $wpdb;
		do_action( 'go-live-update-urls/database/before-update', $old_url, $new_url, $tables, $this );
		$tables = apply_filters( 'go-live-update-urls/database/update-tables', $tables, $this );
		$updaters = (array) Repo::instance()->get_updaters();

		// If the new domain is the old one with a new sub-domain like www.
		if ( strpos( $new_url, $old_url ) !== false ) {
			list( $subdomain ) = explode( '.', $new_url );
			$double_subdomain = $subdomain . '.' . $new_url;
		}

		$serialized = new Serialized( $old_url, $new_url );
		$serialized->update_all_serialized_tables( $tables );
		if ( ! empty( $double_subdomain ) ) {
			$serialized = new Serialized( $double_subdomain, $new_url );
			$serialized->update_all_serialized_tables( $tables );
		}

		$get_columns_query = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$wpdb->dbname}' AND TABLE_NAME=%s";

		foreach ( (array) $tables as $table ) {
			$columns = $wpdb->get_col( $wpdb->prepare( $get_columns_query, $table ) );
			foreach ( $columns as $_column ) {
				$this->update_column( $table, $_column, $old_url, $new_url );

				foreach ( $updaters as $_updater_class ) {
					if ( class_exists( $_updater_class ) ) {
						/* @var Updaters_Abstract $_updater - Individual updater class */
						$_updater = $_updater_class::factory( $table, $_column, $old_url, $new_url );
						$_updater->update_data();
						if ( ! empty( $double_subdomain ) ) {
							$_updater = new $_updater_class( $table, $_column, $double_subdomain, $new_url );
							$_updater->update_data();
						}
					}
				}

				// Fix the double up if this was the old domain with a new subdomain.
				if ( ! empty( $double_subdomain ) ) {
					$this->update_column( $table, $_column, $double_subdomain, $new_url );
					// Fix the emails breaking by being appended the new subdomain.
					$this->update_column( $table, $_column, '@' . $new_url, '@' . $old_url );
				}
			}
		}

		wp_cache_flush();

		do_action( 'go-live-update-urls/database/after-update', $old_url, $new_url, $tables, $this );

		return true;
	}


	/**
	 * Update an individual table's column.
	 *
	 * @param string $table   Table to update.
	 * @param string $column  Column to update.
	 * @param string $old_url Old URL.
	 * @param string $new_url New URL.
	 *
	 * @since 5.3.0
	 *
	 * @return void
	 */
	public function update_column( $table, $column, $old_url, $new_url ) {
		global $wpdb;

		$update_query = 'UPDATE ' . $table . ' SET `' . $column . '` = replace(`' . $column . '`, %s, %s)';
		$wpdb->query( $wpdb->prepare( $update_query, [ $old_url, $new_url ] ) );
	}
}
