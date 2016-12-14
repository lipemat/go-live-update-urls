<?php

namespace Go_Live_Update_Urls\Updaters;

/**
 * JSON
 *
 * When a url is entered into the db using json_encode it
 * get's extra \/ at each slash
 *
 * This updater simulates the same slashes and replaces matches
 *
 * If the url either new or old has a slash we run this
 *
 * Because there is no real good way to detect this which is any lighter
 * than just doing the update we run it on each column
 *
 *
 * @author  Mat Lipe
 * @since   12/13/2016
 *
 * @package Gluu\Updates
 */
class JSON extends _Updater {

	public function update_data(){
		if( !strpos( $this->new, '/' ) && !strpos( $this->old, '/' ) ){
			return false;
		}

		global $wpdb;
		$old = substr( json_encode( $this->old ), 1, -1 );
		$new = substr( json_encode( $this->new ), 1, -1 );

		$update_query = "UPDATE " . $this->table . " SET " . $this->column . " = replace(" . $this->column . ", %s, %s)";
		$wpdb->query( $wpdb->prepare( $update_query, array( $old, $new ) ) );

		return true;
	}

}