<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/public
 */

class Claim_Desk_Public {

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
	 * @param    string    $plugin_name       The name of the plugin.
	 * @param    string    $version           The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/claim-desk-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/claim-desk-public.js', array( 'jquery', 'wp-util' ), $this->version, false );

        // Localize
        wp_localize_script( $this->plugin_name, 'claim_desk_public', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'claim_desk_public_nonce' ),
            'scopes'   => Claim_Desk_Config_Manager::get_scopes()
        ));

	}

    /**
     * Initialize hooks.
     */
    public function init() {
        add_action( 'wp_ajax_claim_desk_get_order_items', array( $this, 'ajax_get_order_items' ) );
        add_action( 'wp_ajax_nopriv_claim_desk_get_order_items', array( $this, 'ajax_get_order_items' ) );
    }

    /**
     * AJAX: Fetch order items for the modal logic.
     */
    public function ajax_get_order_items() {
        check_ajax_referer( 'claim_desk_public_nonce', 'nonce' );

        $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        if ( ! $order_id ) {
            wp_send_json_error( __( 'Invalid Order ID', 'claim-desk' ) );
        }

        // Verify user permission (must be owner of order or admin)
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( __( 'Order not found', 'claim-desk' ) );
        }

        if ( $order->get_user_id() !== get_current_user_id() && ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied', 'claim-desk' ) );
        }

        $items_data = array();
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            if ( ! $product ) continue;

            $image_id = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();

            $items_data[] = array(
                'id' => $item_id, // Line Item ID
                'product_id' => $item->get_product_id(),
                'name' => $item->get_name(),
                'qty' => $item->get_quantity(),
                'image' => $image_url,
                'price' => $order->get_formatted_line_subtotal( $item ),
            );
        }

        wp_send_json_success( $items_data );
    }

    /**
     * Add 'File Claim' button to My Account > Orders actions.
     * 
     * @param array $actions The array of actions for the order.
     * @param WC_Order $order The order object.
     * @return array
     */
    public function add_order_action_button( $actions, $order ) {
        // TODO: specific logic to check if order is eligible for claim
        // e.g., if( $order->get_status() === 'completed' ) ...

        $actions['claim-desk-file'] = array(
            'url'  => '#claim-order-' . $order->get_id(), // Handled by JS
            'name' => __( 'Report Problem', 'claim-desk' ),
            'action' => 'claim-desk-trigger', // Helper class for button
        );

        return $actions;
    }

    /**
     * Add metadata to the order button for JS to read (Order ID).
     * WooCommerce doesn't easily let us add data-attributes to the button via the filter above,
     * so we might rely on the URL or DOM traversal. 
     * However, standard Woo themes usually put the key as a class.
     * We'll implement a footer modal wrapper that JS populates.
     */
    public function add_modal_markup() {
        if ( is_account_page() ) {
            require_once plugin_dir_path( __FILE__ ) . 'partials/claim-desk-public-modal.php';
        }
    }

}
