<?php

/**
 * Go_Live_Update_Urls_Database
 *
 * @author Mat Lipe
 * @since  2/1/2018
 *
 */
class Go_Live_Update_Urls_Database {
	protected $old_url = false;

	protected $new_url = false;

	public $double_subdomain = false; //keep track if going to a subdomain


	/**
	 * Get list of tables we treat as serialized when updating
	 *
	 * @since   5.0.0
	 *
	 * @return array( %table_name% => %table_column% )
	 */
	public function get_serialized_tables() {
		global $wpdb;
		//default tables with serialized data
		$serialized_tables = array(
			$wpdb->options     => 'option_value',
			$wpdb->postmeta    => 'meta_value',
			$wpdb->commentmeta => 'meta_value',
			$wpdb->termmeta    => 'meta_value',
			$wpdb->usermeta    => 'meta_value',
		);

		//we are not going to update user meta if we are not on main blog
		if ( is_multisite() ) {
			$serialized_tables[ $wpdb->sitemeta ] = 'meta_value';
			//WP 5.0.0+
			if ( isset( $wpdb->blogmeta ) ) {
				$serialized_tables[ $wpdb->blogmeta ] = 'meta_value';
			}
		}


		$tables = apply_filters( 'go-live-update-urls-serialized-tables', $serialized_tables );

		return $tables;
	}


	/**
	 * Get the list of tables that were not create by WP core
	 *
	 * @return array
	 */
	public function get_custom_plugin_tables() {
		$core_tables = $this->get_core_tables();
		$all_tables  = $this->get_all_table_names();
		$all_tables  = array_flip( $all_tables );
		foreach ( $core_tables as $_table ) {
			unset( $all_tables[ $_table ] );
		}

		return apply_filters( 'go_live_update_urls_plugin_tables', array_keys( $all_tables ) );
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

		$tables = array(
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
		);

		if ( is_multisite() ) {
			$tables[] = $wpdb->blogs;
			$tables[] = $wpdb->signups;
			$tables[] = $wpdb->site;
			$tables[] = $wpdb->sitemeta;
			$tables[] = $wpdb->sitecategories;
			$tables[] = $wpdb->registration_log;
			$tables[] = $wpdb->blog_versions;
			//WP 5.0.0+
			if ( isset( $wpdb->blogmeta ) ) {
				$tables[ $wpdb->blogmeta ];
			}
		}

		return apply_filters( 'go_live_update_urls_core_tables', $tables );
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

		//Site 1's 'LIKE wp_%' will return all tables in the database
		//so we exclude all possible sub sites e.g. wp_2, wp_3 up to 9
		$not_like = null;
		if ( 1 === (int) $wpdb->blogid && is_multisite() ) {
			for ( $i = 1; $i <= 9; $i ++ ) {
				$not_like .= "'" . $wpdb->prefix . $i . "',";
			}
			$not_like = substr( $not_like, 0, - 1 );
			$query    .= ' AND SUBSTRING(TABLE_NAME,1,4) NOT IN (' . $not_like . ')';
		}
		return $wpdb->get_col( $query );
	}


	/**
	 * @deprecated 5.0.1
	 * @see        Go_Live_Update_Urls_Database::get_all_table_names()
	 */
	public function get_all_tables() {
		$names  = $this->get_all_table_names();
		$tables = array();

		foreach ( $names as $_name ) {
			$tables[] = array(
				'TABLE_NAME' => $_name,
			);
		}

		return $tables;
	}


	/**
	 * Make the actual changes to the database
	 *
	 * @since 5.0.0
	 *
	 * @param string $old_url
	 * @param string $new_url
	 * @param array  $tables
	 *
	 * @todo  split this functionality into its own OOP class
	 *
	 * @return bool
	 */
	public function update_the_database( $old_url, $new_url, array $tables ) {
		global $wpdb;
		$this->old_url = $old_url;
		$this->new_url = $new_url;

		do_action( 'go-live-update-urls/database/before-update', $old_url, $new_url, $tables, $this );

		$updaters = (array) Go_Live_Update_Urls__Updaters__Repo::instance()->get_updaters();

		// If the new domain is the old one with a new sub-domain like www
		if ( strpos( $this->new_url, $this->old_url ) !== false ) {
			list( $subdomain ) = explode( '.', $this->new_url );
			$this->double_subdomain = $subdomain . '.' . $this->new_url;
		}

		$serialized_tables = $this->get_serialized_tables();
		$tables            = apply_filters( 'go-live-update-urls/database/update-tables', $tables, $this );

		// Backward compatibility
		if ( array_values( $tables ) !== $tables ) {
			$tables = (array) array_flip( $tables );
		}

		//Go through each table sent to be updated
		foreach ( (array) $tables as $table ) {
			if ( isset( $serialized_tables[ $table ] ) ) {
				if ( is_array( $serialized_tables[ $table ] ) ) {
					foreach ( $serialized_tables[ $table ] as $column ) {
						$this->update_serialized_table( $table, $column );
					}
				} else {
					$this->update_serialized_table( $table, $serialized_tables[ $table ] );
				}
			}

			$column_query = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$wpdb->dbname}' AND TABLE_NAME=%s";
			$columns      = $wpdb->get_col( $wpdb->prepare( $column_query, $table ) );

			foreach ( $columns as $_column ) {
				$update_query = 'UPDATE ' . $table . ' SET ' . $_column . ' = replace(' . $_column . ', %s, %s)';
				$wpdb->query( $wpdb->prepare( $update_query, array( $this->old_url, $this->new_url ) ) );


				//Run each updater
				//@todo convert all the steps to their own updater class
				foreach ( $updaters as $_updater_class ) {
					if ( class_exists( $_updater_class ) ) {
						/** @var Go_Live_Update_Urls__Updaters__Abstract $_updater */
						$_updater = new $_updater_class( $table, $_column, $this->old_url, $this->new_url );
						$_updater->update_data();
						//run each updater through double sub-domain if applicable
						if ( $this->double_subdomain ) {
							$_updater = new $_updater_class( $table, $_column, $this->double_subdomain, $this->new_url );
							$_updater->update_data();
						}
					}
				}


				//Fix the dub dubs if this was the old domain with a new sub
				if ( $this->double_subdomain ) {
					$wpdb->query( $wpdb->prepare( $update_query, array(
						$this->double_subdomain,
						$this->new_url,
					) ) );
					//Fix the emails breaking by being appended the new subdomain
					$wpdb->query( $wpdb->prepare( $update_query, array(
						'@' . $this->new_url,
						'@' . $this->old_url,
					) ) );
				}
			}
		}

		wp_cache_flush();

		do_action( 'go-live-update-urls/database/after-update', $old_url, $new_url, $tables, $this );

		return true;
	}


	/**
	 * Goes through a table line by line and updates it
	 *
	 * @uses  for tables which may contain serialized arrays
	 *
	 * @since 5.0.0
	 *
	 * @param string $table  the table
	 * @param string $column to column in the table
	 *
	 * @return void
	 */
	protected function update_serialized_table( $table, $column ) {
		global $wpdb;
		$pk = $wpdb->get_results( "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'" );
		if ( empty( $pk[0] ) ) {
			$pk = $wpdb->get_results( "SHOW KEYS FROM $table" );
			if ( empty( $pk[0] ) ) {
				//fail
				return;
			}
		}

		$primary_key_column = $pk[0]->Column_name;

		//Get all the Serialized Rows and Replace them properly
		$rows = $wpdb->get_results( "SELECT $primary_key_column, $column FROM $table WHERE $column LIKE 'a:%' OR $column LIKE 'O:%'" );

		foreach ( $rows as $row ) {
			if ( ! is_serialized( $row->{$column} ) ) {
				continue;
			}

			if ( strpos( $row->{$column}, $this->old_url ) === false ) {
				continue;
			}

			$data = @unserialize( $row->{$column} );

			$clean = $this->replace_tree( $data, $this->old_url, $this->new_url );
			//If we switch to a submain we have to run this again to remove the doubles
			if ( $this->double_subdomain ) {
				$clean = $this->replace_tree( $clean, $this->double_subdomain, $this->new_url );
			}

			$clean = @serialize( $clean );

			$wpdb->query( $wpdb->prepare( "UPDATE $table SET $column=%s WHERE $primary_key_column=%s", $clean, $row->{$primary_key_column} ) );

		}
	}


	/**
	 * Replaces all the occurrences of a string in a multi-dimensional array or Object
	 *
	 * @uses  itself to call each level of the array
	 *
	 * @param iterable|string $data to change
	 * @param string          $old  the old string
	 * @param string          $new  the new string
	 *
	 * @since 5.0.0
	 *
	 * @return mixed
	 *
	 */
	protected function replace_tree( $data, $old, $new ) {
		if ( is_string( $data ) ) {
			return trim( str_replace( $old, $new, $data ) );
		}

		if ( ! is_array( $data ) && ! is_object( $data ) ) {
			return $data;
		}

		foreach ( $data as $key => $item ) {
			if ( is_array( $data ) ) {
				$data[ $key ] = $this->replace_tree( $item, $old, $new );
			} else {
				$data->{$key} = $this->replace_tree( $item, $old, $new );
			}
		}

		return $data;
	}


	protected function hook() {

	}

	//********** SINGLETON **********/


	/**
	 * Instance of this class for use as singleton
	 *
	 * @var self
	 */
	protected static $instance;


	/**
	 * Create the instance of the class
	 *
	 * @static
	 * @return void
	 */
	public static function init() {
		self::instance()->hook();
	}


	/**
	 * Get (and instantiate, if necessary) the instance of the
	 * class
	 *
	 * @static
	 * @return self
	 */
	public static function instance() {
		if ( ! is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
