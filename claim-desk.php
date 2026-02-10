<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * administrative area. This file also includes all of the plugin dependencies.
 *
 * @link              https://example.com
 * @since             1.0.1
 * @package           Claim_Desk
 *
 * @wordpress-plugin
 * Plugin Name:       Claim Desk
 * Plugin URI:        https://example.com/plugin-name
 * Description:       A generalized, multi-industry claim management system for WooCommerce with custom tables.
 * Version:           1.0.3
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       claim-desk
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'CLAIM_DESK_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-claim-desk-activator.php
 */
function activate_claim_desk() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-claim-desk-activator.php';
	Claim_Desk_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-claim-desk-deactivator.php
 */
function deactivate_claim_desk() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-claim-desk-deactivator.php';
	Claim_Desk_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_claim_desk' );
register_deactivation_hook( __FILE__, 'deactivate_claim_desk' );

/**
 * Declare HPOS Compatibility.
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/**
 * The core plugin class that is used to provide internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-claim-desk.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_claim_desk() {

	$plugin = new Claim_Desk();
	$plugin->run();

}
run_claim_desk();
