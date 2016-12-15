<?php
/**
 * Methods for the Go Live Update Urls Plugin
 *
 * @author Mat Lipe
 * @since  2.2
 *
 */
class GoLiveUpdateUrls {
	const NONCE = 'gluu-update-tables';
	const TABLE_INPUT_NAME = 'gluu_table';

	public $oldurl = false;

	public $newurl = false;

	public $double_subdomain = false; //keep track if going to a subdomain

	/*
	 * serialized_tables
	 *
	 * keys are table names
	 * values are table columns
	 *
	 * @var array
	 */
	public  $serialized_tables = array();

	/**
	 * tables
	 *
	 * Tables to update, set during self::maybe_make_updates()
	 *
	 * @var array
	 */
	public $tables = array();


	private function hooks(){
		//If the Form has been submitted make the updates
		if( !empty( $_POST[ 'gluu-submit' ] ) ){
			add_action( 'init', array( $this, 'maybe_run_updates' ) );
		}

		add_action( 'admin_menu', array( $this, 'gluu_add_url_options' ) );
	}


	public function maybe_run_updates(){
		if( !wp_verify_nonce( $_POST[ self::NONCE ], self::NONCE ) ){
			wp_die( __('Ouch! That hurt! You should not be here!', 'go-live-update-urls' ) );
		}

		$this->oldurl = trim( strip_tags( $_POST[ 'oldurl' ] ) );
		$this->newurl = trim( strip_tags( $_POST[ 'newurl' ] ) );

		//backward compatibility with Pro
		if( empty( $_POST[ self::TABLE_INPUT_NAME ] ) ){
			$this->tables = $_POST;
		} else {
			$this->tables = $_POST[ self::TABLE_INPUT_NAME ];
		}

		do_action( 'gluu-before-make-update', $this );

		if( $this->makeTheUpdates() ){
			add_action( 'admin_notices', array( $this, 'success' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'epic_fail' ) );
		}

	}


	public function success(){
		$message = apply_filters( 'go-live-update-urls-success-message', __( 'The URLS in the checked tables have been updated.', 'go-live-update-urls' ) );
		?>
		<div id="message" class="updated fade">
			<p>
				<strong>
					<?php echo $message; ?>
				</strong>
			</p>
		</div>
		<?php
	}


	public function epic_fail(){
		?>
		<div id="message" class="error fade">
			<p>
				<strong><?php _e( 'You must fill out both boxes to make the update!.', 'go-live-update-urls' ); ?></strong>
			</p>
		</div>
		<?php

	}


	/**
	 * Retrieve filtered list of serialized safe database tables
	 *
	 * @since   2.4.0
	 *
	 * @filters go-live-update-urls-serialized-tables - affects tables treated as serialized and checkbox
	 *
	 * @return array( %table_name% => %table_column% )
	 */
	function getSerializedTables(){
		if( empty( $this->serialized_tables ) ){
			global $wpdb;
			//default tables with serialized data
			$this->serialized_tables = array(
				$wpdb->options     => 'option_value', //WP options
				$wpdb->postmeta    => 'meta_value', //post meta - since 2.3.0
				$wpdb->commentmeta => 'meta_value', //comment meta since 2.5.0
			);

			//term meta since WP 4.4
			if( isset( $wpdb->termmeta ) ){
				$this->serialized_tables[ $wpdb->termmeta ] = 'meta_value';
			}

			//we are not going to update user meta if we are not on main blog
			if( is_multisite() ){
				$this->serialized_tables[ $wpdb->sitemeta ] = 'meta_value';
				if( 1 === (int) $wpdb->blogid ){
					$this->serialized_tables[ $wpdb->usermeta ] = 'meta_value';
				}
			}
		}

		//@deprecated
		$tables = apply_filters( 'gluu-seralized-tables', $this->serialized_tables );

		//use this filter
		$tables = apply_filters( 'go-live-update-urls-serialized-tables', $tables );

		return $tables;
	}

	
	/**
	 * Menu Under Tools Menu
	 *
	 * @since 2.0
	 */
	function gluu_add_url_options(){
		add_management_page( "go-live-setting", "Go Live", "manage_options", basename( __FILE__ ), array(
				$this,
				"adminToolsPage"
			) );
	}


	/**
	 * Output the Admin Page for using this plugin
	 *
	 * @since 2.0
	 *
	 */
	function adminToolsPage(){

		wp_enqueue_script( 'gluu-admin-page', self::plugin_url( 'resources/js/admin-page.js'), array( 'jquery'), GLUU_VERSION );

		require( $this->fileHyercy( 'admin-tools-page.php' ) );
	}


	/**
	 * Allows for Overwriting files in the child theme
	 *
	 * @since 2.0
	 *
	 * @since 10.22.13
	 *
	 * @param string $file the name of the file to overwrite
	 */
	function fileHyercy( $file ){
		if( !$theme_file = locate_template( array( 'go-live-update-urls/' . $file ) ) ){
			$theme_file = GLUU_VIEWS_DIR . $file;
		}

		return $theme_file;

	}


	/**
	 * Creates a list of checkboxes for each table
	 *
	 * @since  2.2
	 *
	 * @param array  $tables
	 * @param string $list - uses by js to separate lists
	 * @param bool   $checked
	 *
	 * @return string;
	 *
	 */
	function makeCheckBoxes( $tables, $list, $checked = true ){

		$output = '<ul id="gluu-checkboxes" data-list="' . $list . '">';

		$serialized_tables = $this->getSerializedTables();

		foreach( $tables as $_table ){
			$output .= sprintf( '<li><input name="%s[%s]" type="checkbox" value="%s" class="gluu-wp-core-table" %s/> %s', self::TABLE_INPUT_NAME, $_table, $_table, checked( $checked, true, false), $_table );
			if( in_array( $_table, array_keys( $serialized_tables ) ) ){
				$output .= sprintf( ' - <strong><em>%s</strong></em>', __( 'Serialized Safe', 'go-live-update-urls' ) );
			}
			$output .= '</li>';
		}

		$output .= '</ul>';

		return $output;

	}


	/**
	 * Get the list of tables that were not create by WP core
	 *
	 * @return array
	 */
	public function get_custom_plugin_tables(){
		$core_tables = $this->get_core_tables();
		$all_tables  = wp_list_pluck( self::get_all_tables(), 'TABLE_NAME' );
		$all_tables  = array_flip( $all_tables );
		foreach( $core_tables as $_table ){
			unset( $all_tables[ $_table ] );
		}

		return apply_filters( 'go_live_update_urls_plugin_tables' , array_keys( $all_tables ) );
	}


	/**
	 * Get the list of WP core tables
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function get_core_tables(){
		global $wpdb;

		//Pre WP 4.4
		if( !isset( $wpdb->termmeta ) ){
			$wpdb->termmeta = false;
		}

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

		if( isset( $wpdb->termmeta ) ){
			$tables[] = $wpdb->termmeta;
		}
		if( is_multisite() ){
			$tables[] = $wpdb->blogs;
			$tables[] = $wpdb->signups;
			$tables[] = $wpdb->site;
			$tables[] = $wpdb->sitemeta;
			$tables[] = $wpdb->sitecategories;
			$tables[] = $wpdb->registration_log;
			$tables[] = $wpdb->blog_versions;
		}

		return apply_filters( 'go_live_update_urls_core_tables', $tables );
	}


	/**
	 * get_all_tables
	 *
	 * Get a list of all database table for current install
	 * Includes custom and standard tables
	 *
	 * @static
	 *
	 * @return mixed
	 */
	public static function get_all_tables(){
		global $wpdb;

		$god_query = "SELECT TABLE_NAME FROM information_schema.TABLES where TABLE_SCHEMA='" . $wpdb->dbname . "' AND TABLE_NAME LIKE '" . $wpdb->prefix . "%'";

		//Done this way because like wp_% will return all other tables as well such as wp_2
        //so we exclude all the possibles e.g. wp_2, wp_3, wp_4 up to 9
		$not_like = null;
        if( is_multisite() && $wpdb->blogid == 1 ){
            for( $i = 1; $i <= 9; $i ++ ){
                $not_like .= "'" . $wpdb->prefix . $i . "',";
            }
            $not_like = substr( $not_like, 0, - 1 );
            $god_query .= ' AND SUBSTRING(TABLE_NAME,1,4) NOT IN (' . $not_like . ')';
        }

		return $wpdb->get_results( $god_query );
	}


	/**
	 * Updates the datbase
	 *
	 * @uses  the oldurl and newurl set above
	 *

	 */
    function makeTheUpdates(){
        global $wpdb;

	    if( empty( $this->oldurl ) || empty( $this->newurl ) ){
		    return false;
	    }

        @set_time_limit( 0 );
        @ini_set( 'memory_limit', '256M' );
        @ini_set( 'max_input_time', '-1' );

        $updaters = (array)Go_Live_Update_Urls_Container::get_instance()->get_updaters()->get_updaters();

        // If the new domain is the old one with a new sub-domain like www
        if( strpos( $this->newurl, $this->oldurl ) !== false ){
            list( $subdomain ) = explode( '.', $this->newurl );
            $this->double_subdomain = $subdomain . '.' . $this->newurl;
        }

        $serialized_tables = $this->getSerializedTables();

        //Go through each table sent to be updated
        foreach( array_keys( $this->tables ) as $table ){
	        //backward compatibility with pro
	        if( $table == 'submit' && $table == 'oldurl' && $table == 'newurl' ){
		        continue;
	        }

            if( in_array( $table, array_keys( $serialized_tables ) ) ){
                if( is_array( $serialized_tables[ $table ] ) ){
                    foreach( $serialized_tables[ $table ] as $column ){
                        $this->UpdateSeralizedTable( $table, $column );
                    }
                } else {
                    $this->UpdateSeralizedTable( $table, $serialized_tables[ $table ] );
                }
            }

	        $column_query = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='" . $wpdb->dbname . "' AND TABLE_NAME='" . $table . "'";
	        $columns      = $wpdb->get_col( $column_query );

	        foreach( $columns as $_column ){
		        $update_query = "UPDATE " . $table . " SET " . $_column . " = replace(" . $_column . ", %s, %s)";
		        $wpdb->query( $wpdb->prepare( $update_query, array( $this->oldurl, $this->newurl ) ) );



		        //Run each updater
                //@todo convert all the steps to their own updater class
		        foreach( $updaters as $_updater_class ){
		            if( class_exists( $_updater_class ) ){
			            /** @var \Go_Live_Update_Urls\Updaters\_Updater $_updater */
			            $_updater = new $_updater_class( $table, $_column, $this->oldurl, $this->newurl );
			            $_updater->update_data();
			            //run each updater through double sub-domain if applicable
			            if( $this->double_subdomain ){
				            $_updater = new $_updater_class( $table, $_column, $this->double_subdomain, $this->newurl );
				            $_updater->update_data();
			            }
		            }
		        }


		        //Fix the dub dubs if this was the old domain with a new sub
		        if( $this->double_subdomain ){
			        $wpdb->query( $wpdb->prepare( $update_query, array(
				        $this->double_subdomain,
				        $this->newurl,
			        ) ) );
			        //Fix the emails breaking by being appended the new subdomain
			        $wpdb->query( $wpdb->prepare( $update_query, array(
				        "@" . $this->newurl,
				        "@" . $this->oldurl,
			        ) ) );
		        }
	        }

        }

        wp_cache_flush();

        return true;
    }


	/**
	 * Goes through a table line by line and updates it
	 *
	 * @uses  for tables which may contain seralized arrays
	 * @since 2.1
	 *
	 * @param string $table  the table to go through
	 * @param string $column to column in the table to go through
	 *
	 *
	 */
	function UpdateSeralizedTable( $table, $column = false ){
		global $wpdb;
		$pk = $wpdb->get_results( "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'" );
		if( empty( $pk[ 0 ] ) ){
			$pk = $wpdb->get_results( "SHOW KEYS FROM $table" );
			if( empty( $pk[ 0 ] ) ){
				//fail
				return;
			}
		}

		$primary_key_column = $pk[ 0 ]->Column_name;

		//Get all the Seralized Rows and Replace them properly
		$rows = $wpdb->get_results( "SELECT $primary_key_column, $column FROM $table WHERE $column LIKE 'a:%' OR $column LIKE 'O:%'" );

		foreach( $rows as $row ){
			if( !is_serialized( $row->{$column} ) ){
				continue;
			}

			if( strpos( $row->{$column}, $this->oldurl ) === false ){
				continue;
			}

			$data = @unserialize( $row->{$column} );

			$clean = $this->replaceTree( $data, $this->oldurl, $this->newurl );
			//If we switch to a submain we have to run this again to remove the doubles
			if( $this->double_subdomain ){
				$clean = $this->replaceTree( $clean, $this->double_subdomain, $this->newurl );
			}

			$clean = @serialize( $clean );

			$wpdb->query( $wpdb->prepare( "UPDATE $table SET $column=%s WHERE $primary_key_column=%s", $clean, $row->{$primary_key_column} ) );

		}
	}


	/**
	 * Replaces all the occurances of a string in a multi dementional array or Object
	 *
	 * @uses  itself to call each level of the array
	 * @since 2.1
	 *
	 * @param array|object|string $data to change
	 * @param string              $old  the old string
	 * @param string              $new  the new string
	 * @param                     bool  [optional] $changeKeys to replace string in keys as well - defaults to false
	 *
	 * @since 3.26.13
	 *
	 */
	function replaceTree( $data, $old, $new, $changeKeys = false ){

		if( is_string( $data ) ){
			return trim( str_replace( $old, $new, $data ) );
		}

		if( !is_array( $data ) && !is_object( $data ) ){
			return $data;
		}

		foreach( $data as $key => $item ){

			if( $changeKeys ){
				$key = str_replace( $old, $new, $key );
			}

			if( is_array( $data ) ){
				$data[ $key ] = $this->replaceTree( $item, $old, $new );
			} else {
				$data->{$key} = $this->replaceTree( $item, $old, $new );
			}
		}

		return $data;
	}

	/**************** static ****************************/

	/**
	 * Used along with self::plugin_path() to return path to this plugins files
	 *
	 * @var string
	 */
	private static $plugin_path = false;

	/**
	 * To keep track of this plugins root dir
	 * Used along with self::plugin_url() to return url to plugin files
	 *
	 * @var string
	 */
	private static $plugin_url;


	/**
	 * Retrieve the path this plugins dir
	 *
	 * @param string [$append] - optional path file or name to add
	 *
	 * @return string
	 */
	public static function plugin_path( $append = '' ){

		if( !self::$plugin_path ){
			self::$plugin_path = trailingslashit( dirname( dirname( __FILE__ ) ) );
		}

		return self::$plugin_path . $append;
	}


	/**
	 * Retrieve the url this plugins dir
	 *
	 * @param string [$append] - optional path file or name to add
	 *
	 * @return string
	 */
	public static function plugin_url( $append = '' ){

		if( !self::$plugin_url ){
			self::$plugin_url = trailingslashit( plugins_url( basename( self::plugin_path() ),  dirname( dirname( __FILE__ ) ) ) );
		}

		return self::$plugin_url . $append;
	}


	//********** SINGLETON FUNCTIONS **********/

	/**
	 * Instance of this class for use as singleton
	 */
	private static $instance;


	/**
	 * Create the instance of the class
	 *
	 * @static
	 * @return void
	 */
	public static function init(){
		self::get_instance()->hooks();
	}


	/**
	 * Get (and instantiate, if necessary) the instance of the
	 * class
	 *
	 * @static
	 * @return self
	 */
	public static function get_instance(){
		if( !is_a( self::$instance, __CLASS__ ) ){
			self::$instance = new self();
		}

		return self::$instance;
	}

}
