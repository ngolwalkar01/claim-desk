<?php

/**
 * Fired during plugin deactivation
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes
 */

class Claim_Desk_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        // Typically, we do NOT drop tables on deactivation to preserve data.
        // Tables are usually dropped only on 'uninstall'.
	}

}
