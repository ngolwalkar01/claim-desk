<?php
/**
 * Fired during plugin activation
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes
 */

class Claim_Desk_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::create_tables();
	}

	/**
	 * Create Custom Tables for Claims and Claim Items.
	 * 
	 * @since 1.0.0
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_claims = $wpdb->prefix . 'cd_claims';
		$table_items = $wpdb->prefix . 'cd_claim_items';

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// 1. Claims Header Table
		$sql_claims = "CREATE TABLE $table_claims (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			order_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			type_slug varchar(50) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			resolution_type varchar(20) DEFAULT NULL,
			admin_remarks text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			KEY user_id (user_id),
			KEY type_slug (type_slug),
			KEY status (status)
		) $charset_collate;";

		dbDelta( $sql_claims );

		// 2. Claim Items Table
		$sql_items = "CREATE TABLE $table_items (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			claim_id bigint(20) NOT NULL,
			order_item_id bigint(20) NOT NULL,
			product_id bigint(20) NOT NULL,
			qty_total int(11) NOT NULL,
			qty_claimed int(11) NOT NULL,
			reason_slug varchar(50) NOT NULL,
			dynamic_data longtext DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY claim_id (claim_id),
			KEY product_id (product_id)
		) $charset_collate;";

		dbDelta( $sql_items );
	}

}
