<?php

namespace Go_Live_Update_Urls\Updaters;

use Go_Live_Update_Urls\Database;

/**
 * Base Abstract for any URL updating classes.
 *
 * @author  OnPoint Plugins
 * @since   6.0.0
 */
abstract class Updaters_Abstract {
	/**
	 * The database table.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The database column.
	 *
	 * @var string
	 */
	protected $column;

	/**
	 * The old URL.
	 *
	 * @var string
	 */
	protected $old;

	/**
	 * The new URL.
	 *
	 * @var string
	 */
	protected $new;


	/**
	 * Go_Live_Update_Urls__Updaters__Abstract constructor.
	 *
	 * @param string $table   Table to update.
	 * @param string $column  Column to update.
	 * @param string $old_url Old URL.
	 * @param string $new_url New URL.
	 */
	public function __construct( $table, $column, $old_url, $new_url ) {
		$this->table = $table;
		$this->column = $column;
		$this->old = $old_url;
		$this->new = $new_url;
	}


	/**
	 * Update this table and column.
	 *
	 * @param string $old_url Old URL.
	 * @param string $new_url New URL.
	 *
	 * @return void
	 */
	protected function update_column( $old_url, $new_url ) {
		Database::instance()->update_column( $this->table, $this->column, $old_url, $new_url );
	}


	/**
	 * Filter the new or old url based on this particular updater's logic.
	 *
	 * @param string $url - Either the old or new URL.
	 *
	 * @return string
	 */
	abstract public function apply_rule_to_url( $url );


	/**
	 * The method which is called to actually run the update
	 * using this updater.
	 *
	 * @return bool
	 */
	abstract public function update_data();


	/**
	 * Factory to get constructed class .
	 *
	 * @param string $table   Table to update.
	 * @param string $column  Column to update.
	 * @param string $old_url Old URL.
	 * @param string $new_url New URL.
	 *
	 * @since 5.3.0
	 *
	 * @return static
	 */
	public static function factory( $table, $column, $old_url, $new_url ) {
		return new static( $table, $column, $old_url, $new_url );
	}
}
