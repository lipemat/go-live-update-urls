<?php
/**
 * Methods for the Go Live Update Urls Plugin
 * @author Mat Lipe
 * @since 2.2
 * 
 * @since 2.26.14
 * 
 * @TODO Cleanup the Names and formatting
 */
class GoLiveUpdateUrls{
    var $oldurl = false;
    var $newurl = false;
    var $double_subdomain = false; //keep track if going to a subdomain
    
    //Keys are table names, values are table columns
    //set in self::__construct()
   public $seralized_tables = array();
    
    
    /**
     * @since 2.2
     * 
     * @since 10.22.13
     */
    function __construct(){
        global $wpdb;
        $pf = $wpdb->prefix;
        //Add the settings to the admin menu
        add_action('admin_menu', array( $this,'gluu_add_url_options') );
        
        //Add the CSS
        add_action( 'admin_head', array( $this,'css') );

        
        //default tables with seralized data
        $this->seralized_tables = array( 
             $pf.'options'      => 'option_value', //wordpres options
             $pf.'postmeta'     => 'meta_value', //post meta data - since 2.3.0
             $pf.'rg_form_meta' => 'display_meta' //gravity forms
        ); 
                    
    }
    
    
    /**
     * Retrieve filtered list of seralize safe database tables
     *
     * @since 2.4.0
     * 
     * @filters apply_filters( 'gluu-seralized-tables', $this->seralized_tables ); - effects makeCheckBoxes as well
     *
     * @return array( %table_name% => %table_column% )
     */
    function getSerializedTables(){
        static $tables = false;
        if( $tables ) return $tables;
        
        return $tables = apply_filters( 'gluu-seralized-tables', $this->seralized_tables );
  
    }
    
    
    /**
     * For adding Css to the admin 
     *
     * @since 2.0
     */
    function css(){
      ?><style type="text/css"><?php
            include( $this->fileHyercy('go-live-update-urls.css') );
      ?></style><?php
        
    }
    

    /**
     * Menu Under Tools Menu
     * 
     * @since 2.0
     */
    function gluu_add_url_options(){
       add_management_page("go-live-setting", "Go Live", "manage_options", basename(__FILE__), array( $this,"adminToolsPage") );
    }




    /**
     * Output the Admin Page for using this plugin
     * 
     * @since 2.0
     * 
     */
    function adminToolsPage(){
        global $table_prefix;

        //If the Form has been submitted make the updates
        if( isset( $_POST['gluu-submit'] ) ){

           check_admin_referer(plugin_basename( __FILE__ ), 'gluu-manage-options');
            
           if( !wp_verify_nonce($_POST[ 'gluu-manage-options' ], plugin_basename(__FILE__)) ){
               wp_die('Ouch! That hurt! You should not be here!' );
           }
            $this->oldurl = trim( strip_tags( $_POST['oldurl'] ) );
            $this->newurl = trim( strip_tags( $_POST['newurl'] ) );
        
            if( $this->makeTheUpdates() ){
                echo '<div id="message" class="updated fade"><p><strong>URLs have been updated.</p></strong></div>';
            } else {
                echo '<div class="error"><p><strong>You must fill out both boxes to make the update!</p></strong></div>';
            }
        }
        
        $nonce = wp_nonce_field( plugin_basename( __FILE__ ), 'gluu-manage-options', true, false );
        
        require( $this->fileHyercy('admin-tools-page.php') );
} 


   /**
    * Allows for Overwritting files in the child theme
    * @since 2.0
    * 
    * @since 10.22.13
    * @param string $file the name of the file to overwrite
    */  
    function fileHyercy( $file){
        if ( !$theme_file = locate_template(array('go-live-update-urls/'.$file)) ) {
             $theme_file = GLUU_VIEWS_DIR . $file;
        } 
        return $theme_file;
    
    }



   /**
    * Creates a list of checkboxes for each table
    * 
    * @since 2.2
    * 
    * @since 10.23.13
    * @uses by the view admin-tools-page.php
    * 
    * @filter 'gluu_table_checkboxes' with 2 param
    *     * $output - the html formatted checkboxes
    *     * $tables - the complete tables object
    * 
    */
    function makeCheckBoxes(){ 
         global $wpdb;

         $god_query = "SELECT TABLE_NAME FROM information_schema.TABLES where TABLE_SCHEMA='".$wpdb->dbname."' AND TABLE_NAME LIKE '".$wpdb->prefix."%'"; 
         
         
         //Done this way because like wp_% will return all other tables as well such as wp_2
         if( is_multisite() ){
            if( $wpdb->blogid == 1 ){
                for( $i = 1; $i <= 9; $i++ ){
                    $not_like .= "'".$wpdb->prefix.$i."',";
                }
                $not_like = substr($not_like, 0, -1);
                $god_query .= ' AND SUBSTRING(TABLE_NAME,1,4) NOT IN ('. $not_like .')';
            }
         }
         
         
         $tables = $wpdb->get_results($god_query);
         
         $output =  '<ul id="gluu-checkboxes">';
         
         $seralized_tables = $this->getSerializedTables();
         
          foreach($tables as $v){
             if(in_array( $v->TABLE_NAME, array_keys($seralized_tables)) ){
                $output .= sprintf('<li><input name="%s" type="checkbox" value="%s" checked /> %s - <strong><em>Seralized Safe</strong></em></li>',$v->TABLE_NAME,$v->TABLE_NAME,$v->TABLE_NAME);
             } else {
                $output .= sprintf('<li><input name="%s" type="checkbox" value="%s" checked /> %s</li>',$v->TABLE_NAME,$v->TABLE_NAME,$v->TABLE_NAME);
             }
         }
          
         $output .= '</ul>';
          
         return apply_filters('gluu_table_checkboxes', $output, $tables ); 
          
         
    }

/**
 * Updates the datbase
 * 
 * @uses the oldurl and newurl set above
 * @since 10.22.13
 * 

 */
function makeTheUpdates(){
    
    //in case of large tables
    @set_time_limit( 0 );
    @ini_set( 'memory_limit',           '256M' );
    @ini_set( 'max_input_time',         '-1' );

    global $wpdb;
    
    $oldurl = $this->oldurl;
    $newurl = $this->newurl;
    
    //If a box was empty
    if( $oldurl == '' || $newurl == '' ){
        return false;
    }
    
    // If the new domain is the old one with a new subdomain like www
    if( strpos($newurl, $oldurl) !== false) {
        list( $subdomain ) = explode( '.', $newurl );
        $this->double_subdomain = $subdomain . '.' . $newurl;  //Create a match to what the broken one will be
    }

    $seralized_tables = $this->getSerializedTables();

    
    //Go throuch each table sent to be updated
    foreach($_POST as $v => $i){
        
        //Send the options table through the seralized safe Update
        if( in_array($v, array_keys($seralized_tables)) ){ 
          $this->UpdateSeralizedTable($v, $seralized_tables[$v]); 
        }
        
        if($v != 'submit' && $v != 'oldurl' && $v != 'newurl'){

            $god_query = "SELECT COLUMN_NAME FROM information_schema.COLUMNS where TABLE_SCHEMA='".$wpdb->dbname."' and TABLE_NAME='".$v."'";
            $all = $wpdb->get_results($god_query);
            foreach($all as $t){
                $update_query = "UPDATE ".$v." SET ".$t->COLUMN_NAME." = replace(".$t->COLUMN_NAME.", '".$oldurl."','".$newurl."')";
                //Run the query
                $wpdb->query($update_query);
          
                
                //Fix the dub dubs if this was the old domain with a new sub
                if( $this->double_subdomain ){
                    $update_query = "UPDATE ".$v." SET ".$t->COLUMN_NAME." = replace(".$t->COLUMN_NAME.", '".$this->double_subdomain."','".$newurl."')";
                    //Run the query
                    $wpdb->query($update_query);
                    
                    //Fix the emails breaking by being appended the new subdomain
                    $update_query = "UPDATE ".$v." SET ".$t->COLUMN_NAME." = replace(".$t->COLUMN_NAME.", '@".$newurl."','@".$oldurl."')";
                    $wpdb->query($update_query);
                }

            }
        }
    }
    return true;
}


    /**
     * Goes through a table line by line and updates it
     * 
     * @uses for tables which may contain seralized arrays
     * @since 2.1
     * 
     * @param string $table the table to go through
     * @param string $column to column in the table to go through
     * 
     * @since 2.26.14
     *
     */
    function UpdateSeralizedTable( $table, $column = false ){
        global $wpdb;
        $pk = $wpdb->get_results("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
        $primary_key_column = $pk[0]->Column_name;

        //Get all the Seralized Rows and Replace them properly
        $rows = $wpdb->get_results("SELECT $primary_key_column, $column FROM $table WHERE $column LIKE 'a:%' OR $column LIKE 'O:%'");

        foreach( $rows as $row ){
            if( !is_serialized($row->{$column}) ) continue;
            
            if( strpos($row->{$column}, $this->oldurl) === false ) continue;
            
            $data = @unserialize($row->{$column});

            $clean = $this->replaceTree($data, $this->oldurl, $this->newurl);
            //If we switch to a submain we have to run this again to remove the doubles
            if( $this->double_subdomain ){
                  $clean = $this->replaceTree($clean, $this->double_subdomain, $this->newurl); 
            }
			
			$clean = @serialize($clean);

            $wpdb->query($wpdb->prepare( "UPDATE $table SET $column=%s WHERE $primary_key_column=%s", $clean, $row->{$primary_key_column} ) ); 
           
        }
    }
    
    
    
    /**
     * Replaces all the occurances of a string in a multi dementional array or Object
     * 
     * @uses itself to call each level of the array
     * @since 2.1
     * 
     * @param array|object|string $data to change
     * @param string $old the old string
     * @param string $new the new string
     * @param bool [optional] $changeKeys to replace string in keys as well - defaults to false
     * 
     * @since 3.26.13
     * 
     */
    function replaceTree( $data, $old, $new, $changeKeys = false ){
        
        if( is_string($data) ){
            return trim(str_replace( $old, $new, $data ));            
        }
        
        if( !is_array( $data) && !is_object($data) ){
            return $data;
        }
        
            
        foreach( $data as $key => $item ){
           
            if( $changeKeys ){
                $key = str_replace( $old, $new, $key );
            }
            
            if( is_array( $data)  ){
                    $data[$key] = $this->replaceTree($item, $old, $new);
            } else {
                    $data->{$key} = $this->replaceTree($item, $old, $new);
            }
        }
        return $data;
    }
    
    
    

}
