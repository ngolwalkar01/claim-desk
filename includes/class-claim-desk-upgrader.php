<?php
/**
 * Handles schema upgrades for Claim Desk.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Claim_Desk_Upgrader {

	/**
	 * DB schema version option key.
	 */
	const OPTION_DB_VERSION = 'claim_desk_db_version';

	/**
	 * Current schema version.
	 */
	const DB_VERSION = '1.1.0';

	/**
	 * Run upgrade if needed.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$stored_version = get_option( self::OPTION_DB_VERSION, '1.0.0' );
		if ( version_compare( (string) $stored_version, self::DB_VERSION, '>=' ) ) {
			return;
		}

		require_once CLAIM_DESK_PLUGIN_PATH . 'includes/class-claim-desk-activator.php';
		Claim_Desk_Activator::activate();
		update_option( self::OPTION_DB_VERSION, self::DB_VERSION );
	}
}

