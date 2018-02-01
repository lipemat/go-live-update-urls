<?php

/**
 * _Updater
 *
 * @author  Mat Lipe
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


	abstract public function update_data();
}
