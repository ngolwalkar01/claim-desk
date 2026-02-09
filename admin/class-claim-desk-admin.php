<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/admin
 */

class Claim_Desk_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version           The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/claim-desk-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/claim-desk-admin.js', array( 'jquery' ), $this->version, false );

        // Localize script for AJAX
        wp_localize_script( $this->plugin_name, 'claim_desk_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'claim_desk_admin_nonce' )
        ));

	}

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     * 
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {

        add_submenu_page(
            'woocommerce',
            __( 'Claim Desk', 'claim-desk' ),
            __( 'Claims', 'claim-desk' ),
            'manage_options',
            'claim-desk',
            array( $this, 'display_plugin_setup' )
        );

    }

    /**
     * Render the main setup page with Tabs.
     */
    public function display_plugin_setup() {
        $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'claims';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Claim Desk', 'claim-desk' ); ?></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=claim-desk&tab=claims" class="nav-tab <?php echo $active_tab == 'claims' ? 'nav-tab-active' : ''; ?>"><?php _e( 'All Claims', 'claim-desk' ); ?></a>
                <a href="?page=claim-desk&tab=config" class="nav-tab <?php echo $active_tab == 'config' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Configuration', 'claim-desk' ); ?></a>
            </nav>

            <div class="claim-desk-content">
                <?php
                if ( $active_tab == 'claims' ) {
                    $this->display_plugin_dashboard();
                } else {
                    $this->display_plugin_configuration();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Claims List (Tab Content).
     */
    public function display_plugin_dashboard() {
        require_once plugin_dir_path( __FILE__ ) . 'partials/claim-desk-admin-display.php';
    }

    /**
     * Render the Configuration (Tab Content).
     */
    public function display_plugin_configuration() {
        require_once plugin_dir_path( __FILE__ ) . 'partials/claim-desk-admin-config.php';
    }

}
