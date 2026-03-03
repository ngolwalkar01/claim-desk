<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Claim_Desk {

	/**
	 * The loaders that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Claim_Desk_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'CLAIM_DESK_VERSION' ) ) {
			$this->version = CLAIM_DESK_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'claim-desk';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Claim_Desk_Loader. Orchestrates the hooks of the plugin.
	 * - Claim_Desk_i18n. Defines internationalization functionality.
	 * - Claim_Desk_Admin. Defines all hooks for the admin area.
	 * - Claim_Desk_Public. Defines all hooks for the public side of the site.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-claim-desk-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-claim-desk-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-claim-desk-admin.php';

        /**
         * The class responsible for managing configuration options.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-claim-desk-config-manager.php';

        /**
         * The class responsible for database operations.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-claim-desk-db-handler.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-claim-desk-claim-data.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-claim-desk-email-manager.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-claim-desk-reminder-service.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-claim-desk-upgrader.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-claim-desk-public.php';

		$this->loader = new Claim_Desk_Loader();
		Claim_Desk_Upgrader::maybe_upgrade();

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Claim_Desk_Admin( $this->get_plugin_name(), $this->get_version() );
        $config_manager = new Claim_Desk_Config_Manager();
        $config_manager->init();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'process_status_update' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Claim_Desk_Public( $this->get_plugin_name(), $this->get_version() );
        $plugin_public->init(); // creating ajax hooks

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        
        // Add Button to My Account > Orders
        $this->loader->add_filter( 'woocommerce_my_account_my_orders_actions', $plugin_public, 'add_order_action_button', 10, 2 );

		// Render per-product claim UI on My Account > View Order.
		$this->loader->add_action( 'woocommerce_order_details_after_order_table', $plugin_public, 'render_order_claim_interface' );
        
        // Register Shortcode
		add_shortcode( 'claim_desk_wizard', array( $plugin_public, 'render_wizard' ) );

	}

	/**
	 * Register email and reminder hooks.
	 *
	 * @since 1.0.0
	 */
	private function define_email_hooks() {
		$email_manager    = new Claim_Desk_Email_Manager();
		$reminder_service = new Claim_Desk_Reminder_Service();

		$this->loader->add_filter( 'woocommerce_email_classes', $email_manager, 'register_email_classes' );
		$this->loader->add_action( 'claim_desk_claim_created', $email_manager, 'trigger_claim_created_emails', 10, 1 );
		$this->loader->add_action( 'claim_desk_claim_status_updated', $email_manager, 'trigger_claim_status_updated_email', 10, 2 );
		$this->loader->add_action( 'claim_desk_check_reminders', $reminder_service, 'process_pending_claims', 10, 0 );
		$this->loader->add_action( 'init', $reminder_service, 'maybe_schedule_event', 20, 0 );
		$this->loader->add_action( 'claim_desk_claim_created', $reminder_service, 'handle_claim_created', 10, 1 );
		$this->loader->add_action( 'claim_desk_claim_status_updated', $reminder_service, 'handle_claim_status_updated', 10, 2 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->define_email_hooks();
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Claim_Desk_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
