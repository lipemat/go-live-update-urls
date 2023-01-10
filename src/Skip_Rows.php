<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Traits\Singleton;

/**
 * Facilitate skipping particular rows during
 * database update.
 *
 * Used primarily to handle serialized data, which
 * didn't update because a saved PHP class is missing.
 *
 * @since  6.5.0
 */
class Skip_Rows {
	use Singleton;

	/**
	 * Current table ids are being assigned to.
	 *
	 * @var string
	 */
	protected $table = '';

	/**
	 * Holds the primary keys for tables,
	 * so we can reference during our update queries.
	 *
	 * @var array
	 */
	protected $primary_keys = [];

	/**
	 * The current id to skip if `skip_row` is called.
	 *
	 * @var int
	 */
	protected $row_id = 0;

	/**
	 * Full list of tables and ids to skip.
	 *
	 * @var array
	 */
	protected $skip = [];


	/**
	 * Set the table, which subsequent calls to `skip_row` will
	 * be assigned to.
	 *
	 * @param string $table       - Database table.
	 * @param string $primary_key - Primary key field for this table.
	 *
	 * @return void
	 */
	public function set_current_table( $table, $primary_key ) {
		$this->table = $table;
		$this->primary_keys[ $table ] = $primary_key;
	}


	/**
	 * Skip a row in the current table by calling
	 * `skip_row` after setting this id.
	 *
	 * Allows accessing and id down the stack without
	 * passing it to every level.
	 *
	 * @param int $db_id - ID of database table row.
	 *
	 * @return void
	 */
	public function set_current_row_id( $db_id ) {
		$this->row_id = $db_id;
	}


	/**
	 * Skip a row in the current table.
	 *
	 * @return void
	 */
	public function skip_current() {
		if ( '' === $this->table || 0 === $this->row_id ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'You must set a table and DB id before skipping a row.', 'go-live-update-urls' ), '6.5.0' );
		}
		if ( empty( $this->skip[ $this->table ] ) || ! in_array( $this->row_id, $this->skip[ $this->table ], true ) ) {
			$this->skip[ $this->table ][] = $this->row_id;
		}
	}


	/**
	 * Get any db ids to be skipped for a table.
	 *
	 * @param string $table - Database table.
	 *
	 * @return int[]|null
	 */
	public function get_skipped( $table ) {
		if ( ! empty( $this->skip[ $table ] ) ) {
			return $this->skip[ $table ];
		}

		return null;
	}


	/**
	 * Get the primary key for a table, which was provided
	 * during `set_current_table`.
	 *
	 * @param string $table - Database table.
	 *
	 * @return string|null
	 */
	public function get_primary_key( $table ) {
		if ( ! isset( $this->primary_keys[ $table ] ) ) {
			return null;
		}
		return $this->primary_keys[ $table ];
	}


	/**
	 * Reset all skips for a fresh class.
	 *
	 * @return void
	 */
	public static function reset() {
		static::$instance = null;
	}
}
