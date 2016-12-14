<?php

namespace Go_Live_Update_Urls\Updaters;

/**
 * _Updater
 *
 * @author  Mat Lipe
 * @since   12/13/2016
 *
 * @package Gluu\Updates
 */
abstract class _Updater {
	protected $table;

	protected $column;

	protected $old;

	protected $new;


	public function __construct( $table, $column, $old, $new ){
		$this->table  = $table;
		$this->column = $column;
		$this->old    = $old;
		$this->new    = $new;
	}


	abstract public function update_data();
}