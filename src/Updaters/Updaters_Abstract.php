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
	 * Updaters Constructor
	 *
	 * @param string $table   Table to update.
	 * @param string $column  Column to update.
	 * @param string $old_url Old URL.
	 * @param string $new_url New URL.
	 */
	final public function __construct( $table, $column, $old_url, $new_url ) {
		$this->table = $table;
		$this->column = $column;
		$this->old = $old_url;
		$this->new = $new_url;
	}


	/**
	 * Filter the new or old url based on this particular updater's logic.
	 *
	 * @param string $url - Either the old or new URL.
	 *
	 * @return string
	 */
	abstract public static function apply_rule_to_url( $url );


	/**
	 * Get the priority of this updater.
	 *
	 * The higher the number, the sooner it will run in the stack.
	 *
	 * @since 6.10.0
	 *
	 * @return int
	 */
	abstract public static function get_priority(): int;


	/**
	 * Update this table and column.
	 *
	 * @param string $old_url - Old URL.
	 * @param string $new_url - New URL.
	 *
	 * @return int
	 */
	protected function update_column( $old_url, $new_url ): int {
		return Database::instance()->update_column( $this->table, $this->column, $old_url, $new_url );
	}


	/**
	 * Count occurrences of the old URL in this table's column.
	 *
	 * @return int
	 */
	public function count_urls(): int {
		$old_url = static::apply_rule_to_url( $this->old );
		if ( $old_url === $this->old ) {
			return 0;
		}
		return Database::instance()->count_column_urls( $this->table, $this->column, $old_url );
	}


	/**
	 * Get the old and new URLs formatted for replacement.
	 *
	 * @since 6.10.0
	 *
	 * @param string $old_url - Old URL.
	 * @param string $new_url - New URL.
	 *
	 * @return array{new: string, old: string}
	 */
	public static function get_formatted( string $old_url, string $new_url ): array {
		/**
		 * If the old URL has a "/" in it, but the new URL doesn't, we add a / to the beginning of each URL to create a selector to look for.
		 *
		 * Without: domain.com
		 * With: \\\/domain.com
		 */
		if ( static::is_appending_update( $old_url, $new_url ) ) {
			$prefix = static::apply_rule_to_url( '/' );
			return [
				'old' => $prefix . $new_url,
				'new' => $prefix . static::apply_rule_to_url( $new_url ),
			];
		}

		return [
			'old' => static::apply_rule_to_url( $old_url ),
			'new' => static::apply_rule_to_url( $new_url ),
		];
	}


	/**
	 * Is this updater appending to a previous update made by
	 * by Database::update_column()?
	 *
	 * Used for
	 * - Prevent duplicate counts.
	 * - Fix previous updates which conflict with this rule.
	 *
	 * @since 6.10.0
	 *
	 * @see   Database::update_column
	 *
	 * @param string $old_url - Old URL.
	 * @param string $new_url - New URL.
	 *
	 * @return bool
	 */
	public static function is_appending_update( string $old_url, string $new_url ): bool {
		return static::apply_rule_to_url( $old_url ) === $old_url && static::apply_rule_to_url( $new_url ) !== $new_url;
	}


	/**
	 * Update the old over escaped URL with the new over escaped URL if the entered
	 * old URL or new URL has a "/" in it.
	 * If no URL has a "/" in it, we don't need to run this.
	 *
	 * @return int
	 */
	public function update_data(): int {
		if ( false === strpos( $this->old, '/' ) && false === strpos( $this->new, '/' ) ) {
			return 0;
		}
		$formatted = static::get_formatted( $this->old, $this->new );
		$count = $this->update_column( $formatted['old'], $formatted['new'] );
		if ( static::is_appending_update( $this->old, $this->new ) ) {
			return 0;
		}
		return $count;
	}


	/**
	 * Factory to get constructed class
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
