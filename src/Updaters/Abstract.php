<?php

/**
 * _Updater
 *
 * @author  OnPoint Plugins
 * @since   5.0.0
 *
 */
abstract class Go_Live_Update_Urls__Updaters__Abstract {
	protected $table;

	protected $column;

	protected $old;

	protected $new;


	public function __construct( $table, $column, $old, $new ) {
		$this->table  = $table;
		$this->column = $column;
		$this->old    = $old;
		$this->new    = $new;
	}


	protected function update_column( $old, $new ) {
		global $wpdb;

		$update_query = 'UPDATE ' . $this->table . ' SET ' . $this->column . ' = replace(' . $this->column . ', %s, %s)';
		$wpdb->query( $wpdb->prepare( $update_query, array( $old, $new ) ) );
	}


	abstract public function apply_rule_to_url( $url );

	abstract public function update_data();
}
