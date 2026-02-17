<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/public
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

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
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'claim_desk_public_nonce' ),
            'scopes'      => Claim_Desk_Config_Manager::get_scopes(),
            'resolutions' => Claim_Desk_Config_Manager::get_resolutions(),
            'problems'    => Claim_Desk_Config_Manager::get_problems(),
            'conditions'  => Claim_Desk_Config_Manager::get_conditions()
        ));

	}

    /**
     * Initialize hooks.
     */
    public function init() {
        add_action( 'wp_ajax_claim_desk_get_order_items', array( $this, 'ajax_get_order_items' ) );
        add_action( 'wp_ajax_nopriv_claim_desk_get_order_items', array( $this, 'ajax_get_order_items' ) );
        
        add_action( 'wp_ajax_claim_desk_submit_claim', array( $this, 'ajax_submit_claim' ) );
        add_action( 'wp_ajax_nopriv_claim_desk_submit_claim', array( $this, 'ajax_submit_claim' ) );
    }

    /**
     * AJAX: Submit a new claim.
     */
    public function ajax_submit_claim() {
        check_ajax_referer( 'claim_desk_public_nonce', 'nonce' );

        $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $scope    = isset( $_POST['scope'] ) ? sanitize_key( $_POST['scope'] ) : '';
        $items    = isset( $_POST['items'] ) ? json_decode( wp_unslash( $_POST['items'] ), true ) : [];
        $form_data = isset( $_POST['form_data'] ) ? json_decode( wp_unslash( $_POST['form_data'] ), true ) : [];

        // 1. Validation
        if ( ! $order_id || ! $scope || empty( $items ) ) {
            wp_send_json_error( __( 'Missing required data (Order, Scope, or Items).', 'claim-desk' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( __( 'Order not found.', 'claim-desk' ) );
        }

        // Check ownership
        if ( $order->get_user_id() !== get_current_user_id() && ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'claim-desk' ) );
        }

        // 2. Prepare Data for Insertion
        $db = new Claim_Desk_DB_Handler();

        // Create Header
        $claim_id = $db->create_claim( array(
            'order_id' => $order_id,
            'user_id'  => get_current_user_id(),
            'type_slug' => $scope,
            'status'   => 'pending' 
        ));

        if ( ! $claim_id ) {
            wp_send_json_error( __( 'Failed to create claim record.', 'claim-desk' ) );
        }

        // Create Items
        foreach ( $items as $item_id => $qty ) {
            $item_id = intval($item_id); // Order Item ID (line item id)
            $qty = intval($qty);

            // Fetch product ID from order item
            $order_item = $order->get_item( $item_id );
            
            // Safety check if item belongs to order
            if ( ! $order_item ) continue; 

            $product_id = $order_item->get_product_id();
            $qty_total = $order_item->get_quantity();

            // Extract reason from form data? 
            // In our simple form, we might have one reason for the whole claim OR per item?
            // The step 3 UI has a single Reason radio.
            // Let's assume the single reason applies to all selected items for this MVP,
            // OR we store it in dynamic_data.
            // The table `wp_cd_claim_items` has `reason_slug`.
            
            // Extract single reason for now
            $reason_slug = '';
            foreach($form_data as $field) {
                if($field['name'] === 'claim_reason') {
                    $reason_slug = sanitize_key($field['value']);
                    break;
                }
            }

            // Collect other dynamic fields into JSON
            // e.g. batch_number, description
            $dynamic_fields = array();
            foreach($form_data as $field) {
                if($field['name'] !== 'claim_reason') {
                    $dynamic_fields[$field['name']] = sanitize_text_field($field['value']);
                }
            }

            $db->create_claim_item( array(
                'claim_id'      => $claim_id,
                'order_item_id' => $item_id,
                'product_id'    => $product_id,
                'qty_total'     => $qty_total,
                'qty_claimed'   => $qty,
                'reason_slug'   => $reason_slug,
                'dynamic_data'  => json_encode( $dynamic_fields )
            ));
        }

        // 3. Handle File Uploads
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( ! empty( $_FILES['files'] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );

            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $files = $_FILES['files'];
            $file_count = count( $files['name'] );

            // Limit to 5 files
            if ( $file_count > 5 ) {
                wp_send_json_error( __( 'Maximum 5 files allowed.', 'claim-desk' ) );
            }

            // Allowed file types
            $allowed_types = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
            $allowed_mimes = array(
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp'
            );

            for ( $i = 0; $i < $file_count; $i++ ) {
                // Skip empty files
                if ( empty( $files['name'][$i] ) ) {
                    continue;
                }

                // Check file size (2MB max)
                if ( $files['size'][$i] > 2097152 ) {
                    wp_send_json_error( __( 'File size must not exceed 2MB.', 'claim-desk' ) );
                }

                // Prepare file array for wp_handle_upload
                $file = array(
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i]
                );

                // Validate file type and extension
                $filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
                $ext = $filetype['ext'];
                $type = $filetype['type'];

                // Check if extension and MIME type are allowed
                if ( ! in_array( $ext, $allowed_types ) || ! in_array( $type, $allowed_mimes ) ) {
                    wp_send_json_error( __( 'Invalid file type. Only JPG, PNG, GIF, and WEBP images are allowed.', 'claim-desk' ) );
                }

                // Upload file
                $upload_overrides = array(
                    'test_form' => false,
                    'mimes'     => array(
                        'jpg|jpeg|jpe' => 'image/jpeg',
                        'png'          => 'image/png',
                        'gif'          => 'image/gif',
                        'webp'         => 'image/webp'
                    )
                );

                $uploaded_file = wp_handle_upload( $file, $upload_overrides );

                if ( isset( $uploaded_file['error'] ) ) {
                    wp_send_json_error( $uploaded_file['error'] );
                }

                // Save attachment metadata to database
                $upload_dir = wp_upload_dir();
                $relative_path = str_replace( $upload_dir['basedir'], '', $uploaded_file['file'] );

                $db->save_attachment( array(
                    'claim_id'  => $claim_id,
                    'file_path' => $relative_path,
                    'file_name' => basename( $uploaded_file['file'] ),
                    'file_type' => $uploaded_file['type'],
                    'file_size' => filesize( $uploaded_file['file'] )
                ));
            }
        }

        wp_send_json_success( array(
            'message' => __( 'Claim submitted successfully!', 'claim-desk' ),
            'claim_id' => $claim_id
        ));
    }

    /**
     * AJAX: Fetch order items for the modal logic.
     */
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

        // Fetch existing claims to calculate available quantity
        $db = new Claim_Desk_DB_Handler();
        $claimed_items = $db->get_claimed_items_by_order( $order_id );
        
        // Map claimed quantities by order_item_id
        $claimed_map = array();
        if ( ! empty( $claimed_items ) ) {
            foreach ( $claimed_items as $ci ) {
                if ( ! isset( $claimed_map[ $ci->order_item_id ] ) ) {
                    $claimed_map[ $ci->order_item_id ] = 0;
                }
                $claimed_map[ $ci->order_item_id ] += intval( $ci->qty_claimed );
            }
        }

        $items_data = array();
        $debug_logs = array();

        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            if ( ! $product ) continue;

            $image_id = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();
            
            $original_qty = $item->get_quantity();
            $already_claimed = isset( $claimed_map[ $item_id ] ) ? $claimed_map[ $item_id ] : 0;
            $available_qty = max( 0, $original_qty - $already_claimed );

            $debug_logs[] = "Item ID: $item_id | Original: $original_qty | Claimed Map Value: " . (isset($claimed_map[$item_id]) ? $claimed_map[$item_id] : 'NULL');

            $items_data[] = array(
                'id' => $item_id, // Line Item ID
                'product_id' => $item->get_product_id(),
                'name' => $item->get_name(),
                'qty' => $original_qty,
                'qty_claimed' => $already_claimed,
                'qty_available' => $available_qty,
                'image' => $image_url,
                'price' => $order->get_formatted_line_subtotal( $item ),
            );
        }

        wp_send_json_success( array( 'items' => $items_data, 'debug' => $debug_logs, 'raw_claims' => $claimed_items ) );
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

        $wizard_page_id = get_page_by_path( 'claim-wizard' ); // Improve: get from settings
        $wizard_url = $wizard_page_id ? get_permalink( $wizard_page_id ) : site_url( '/claim-wizard/' );
        $claim_url = add_query_arg( 'order_id', $order->get_id(), $wizard_url );

        $actions['claim-desk-trigger'] = array(
            'url'  => $claim_url,
            'name' => __( 'Report Problem', 'claim-desk' ),
            'action' => 'claim-desk-trigger', // Keeps class but won't trigger modal if JS logic changes
        );

        return $actions;
    }

    /**
     * Add 'Report Problem' button to Order Details page.
     * 
     * @param WC_Order $order
     */
    public function add_order_detail_button( $order ) {
        if ( ! $order ) return;

        if ( $order->get_user_id() !== get_current_user_id() && ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $wizard_page_id = get_page_by_path( 'claim-wizard' );
        $wizard_url = $wizard_page_id ? get_permalink( $wizard_page_id ) : site_url( '/claim-wizard/' );
        $claim_url = add_query_arg( 'order_id', $order->get_id(), $wizard_url );
        ?>
        <p class="order-again">
            <a href="<?php echo esc_url( $claim_url ); ?>" class="button claim-desk-trigger">
                <?php esc_html_e( 'Report Problem', 'claim-desk' ); ?>
            </a>
        </p>
        <?php
    }

    /**
     * Render the Wizard Shortcode.
     */
    public function render_wizard( $atts ) {
        // Enqueue scripts/styles if not already
        $this->enqueue_styles();
        $this->enqueue_scripts();

        ob_start();
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/claim-desk-wizard.php';
        return ob_get_clean();
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
