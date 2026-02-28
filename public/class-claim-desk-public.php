<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/public
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Claim_Desk_Public {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue public styles.
	 */
	public function enqueue_styles() {
		$style_path = plugin_dir_path( __FILE__ ) . 'css/claim-desk-public.css';
		$style_ver  = file_exists( $style_path ) ? (string) filemtime( $style_path ) : $this->version;
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/claim-desk-public.css', array(), $style_ver, 'all' );
	}

	/**
	 * Enqueue public scripts.
	 */
	public function enqueue_scripts() {
		$script_path = plugin_dir_path( __FILE__ ) . 'js/claim-desk-public.js';
		$script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : $this->version;
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/claim-desk-public.js', array( 'jquery' ), $script_ver, true );

		wp_localize_script(
			$this->plugin_name,
			'claim_desk_public',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'claim_desk_public_nonce' ),
				'problems'   => Claim_Desk_Config_Manager::get_problems(),
				'conditions' => Claim_Desk_Config_Manager::get_conditions(),
				'i18n'       => array(
					'loading'         => __( 'Submitting...', 'claim-desk' ),
					'submit'          => __( 'Submit Claim', 'claim-desk' ),
					'server_error'    => __( 'Server error. Please try again.', 'claim-desk' ),
					'select_quantity' => __( 'Please select a quantity first.', 'claim-desk' ),
				),
			)
		);
	}

	/**
	 * Register AJAX actions.
	 */
	public function init() {
		add_action( 'wp_ajax_claim_desk_get_order_items', array( $this, 'ajax_get_order_items' ) );
		add_action( 'wp_ajax_nopriv_claim_desk_get_order_items', array( $this, 'ajax_get_order_items' ) );
		add_action( 'wp_ajax_claim_desk_submit_claim', array( $this, 'ajax_submit_claim' ) );
		add_action( 'wp_ajax_nopriv_claim_desk_submit_claim', array( $this, 'ajax_submit_claim' ) );
	}

	/**
	 * Render per-product claim interface on order details.
	 *
	 * @param WC_Order|int $order Order object or ID.
	 */
	public function render_order_claim_interface( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( absint( $order ) );
		}

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( $order->get_user_id() !== get_current_user_id() && ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$claimed_map = $this->get_claimed_qty_map( $order->get_id() );
		$status_map  = $this->get_claim_status_map( $order->get_id() );
		$claim_window_status = $this->get_order_claim_window_status( $order );
		$claim_items = array();

		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			$total_qty     = absint( $item->get_quantity() );
			$claimed_qty   = isset( $claimed_map[ $item_id ] ) ? absint( $claimed_map[ $item_id ] ) : 0;
			$available_qty = max( 0, $total_qty - $claimed_qty );
			$image_id      = $product->get_image_id();
			$image_url     = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();

			$claim_items[] = array(
				'order_item_id' => absint( $item_id ),
				'product_id'    => absint( $item->get_product_id() ),
				'name'          => $item->get_name(),
				'image'         => $image_url,
				'qty_total'     => $total_qty,
				'qty_claimed'   => $claimed_qty,
				'qty_available' => $available_qty,
				'claim_status'  => isset( $status_map[ $item_id ] ) ? $status_map[ $item_id ] : '',
				'window_allows_claims' => ! empty( $claim_window_status['allowed'] ),
				'window_message'       => isset( $claim_window_status['message'] ) ? $claim_window_status['message'] : '',
			);
		}

		if ( empty( $claim_items ) ) {
			return;
		}

		require plugin_dir_path( __FILE__ ) . 'partials/claim-desk-order-claims.php';
	}

	/**
	 * Add claim action link in orders table.
	 *
	 * @param array    $actions Action list.
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	public function add_order_action_button( $actions, $order ) {
		if ( ! $order instanceof WC_Order ) {
			return $actions;
		}

		$claim_window_status = $this->get_order_claim_window_status( $order );
		$is_allowed          = ! empty( $claim_window_status['allowed'] );
		$message             = isset( $claim_window_status['message'] ) ? (string) $claim_window_status['message'] : '';

		$actions['claim-desk-trigger'] = array(
			'url'    => $is_allowed ? $order->get_view_order_url() . '#cd-order-claims' : '#',
			'name'   => $is_allowed
				? __( 'Start Claim', 'claim-desk' )
				: ( $message ? $message : __( 'Claims can be created only after the order is delivered.', 'claim-desk' ) ),
			'action' => $is_allowed ? 'claim-desk-trigger' : 'claim-desk-trigger-disabled',
		);

		return $actions;
	}

	/**
	 * Backward-compatible shortcode output.
	 *
	 * @param array $atts Shortcode attrs.
	 * @return string
	 */
	public function render_wizard( $atts ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
		if ( ! $order_id ) {
			return '<p>' . esc_html__( 'Please open a valid order to submit a claim.', 'claim-desk' ) . '</p>';
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return '<p>' . esc_html__( 'Order not found.', 'claim-desk' ) . '</p>';
		}

		ob_start();
		$this->render_order_claim_interface( $order );
		return ob_get_clean();
	}

	/**
	 * AJAX: Fetch order items and available claim quantities.
	 */
	public function ajax_get_order_items() {
		check_ajax_referer( 'claim_desk_public_nonce', 'nonce' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		if ( ! $order_id ) {
			wp_send_json_error( __( 'Invalid order ID.', 'claim-desk' ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found.', 'claim-desk' ) );
		}

		if ( $order->get_user_id() !== get_current_user_id() && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'claim-desk' ) );
		}

		$claimed_map = $this->get_claimed_qty_map( $order_id );
		$status_map  = $this->get_claim_status_map( $order_id );
		$claim_window_status = $this->get_order_claim_window_status( $order );
		$items_data  = array();

		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			$original_qty  = absint( $item->get_quantity() );
			$claimed_qty   = isset( $claimed_map[ $item_id ] ) ? absint( $claimed_map[ $item_id ] ) : 0;
			$available_qty = max( 0, $original_qty - $claimed_qty );
			$image_id      = $product->get_image_id();
			$image_url     = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();

			$items_data[] = array(
				'id'            => absint( $item_id ),
				'product_id'    => absint( $item->get_product_id() ),
				'name'          => $item->get_name(),
				'qty'           => $original_qty,
				'qty_claimed'   => $claimed_qty,
				'qty_available' => $available_qty,
				'image'         => $image_url,
				'claim_status'  => isset( $status_map[ $item_id ] ) ? $status_map[ $item_id ] : '',
				'window_allows_claims' => ! empty( $claim_window_status['allowed'] ),
				'window_message'       => isset( $claim_window_status['message'] ) ? $claim_window_status['message'] : '',
			);
		}

		wp_send_json_success( array( 'items' => $items_data ) );
	}

	/**
	 * AJAX: Submit one independent product claim.
	 */
	public function ajax_submit_claim() {
		check_ajax_referer( 'claim_desk_public_nonce', 'nonce' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$order_item_id = isset( $_POST['order_item_id'] ) ? absint( wp_unslash( $_POST['order_item_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$quantity = isset( $_POST['quantity'] ) ? absint( wp_unslash( $_POST['quantity'] ) ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$claim_type = isset( $_POST['claim_type'] ) ? sanitize_key( wp_unslash( $_POST['claim_type'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$problem_type = isset( $_POST['problem_type'] ) ? sanitize_key( wp_unslash( $_POST['problem_type'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$product_condition = isset( $_POST['product_condition'] ) ? sanitize_key( wp_unslash( $_POST['product_condition'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$refund_method = isset( $_POST['refund_method'] ) ? sanitize_key( wp_unslash( $_POST['refund_method'] ) ) : '';

		if ( ! $order_id || ! $order_item_id || ! $product_id || $quantity < 1 ) {
			wp_send_json_error( __( 'Missing required claim data.', 'claim-desk' ) );
		}

		if ( ! in_array( $claim_type, array( 'return', 'exchange' ), true ) ) {
			wp_send_json_error( __( 'Invalid claim type.', 'claim-desk' ) );
		}

		if ( '' === $problem_type || '' === $description || '' === $refund_method ) {
			wp_send_json_error( __( 'Please complete all required fields.', 'claim-desk' ) );
		}

		if ( '' === $product_condition ) {
			$product_condition = 'not-specified';
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found.', 'claim-desk' ) );
		}

		if ( $order->get_user_id() !== get_current_user_id() && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'claim-desk' ) );
		}

		$claim_window_status = $this->get_order_claim_window_status( $order );
		if ( empty( $claim_window_status['allowed'] ) ) {
			$error_message = isset( $claim_window_status['message'] ) && $claim_window_status['message']
				? $claim_window_status['message']
				: __( 'Claims are not allowed for this order.', 'claim-desk' );
			wp_send_json_error( $error_message );
		}

		$order_item = $order->get_item( $order_item_id );
		if ( ! $order_item ) {
			wp_send_json_error( __( 'Order item not found.', 'claim-desk' ) );
		}

		if ( absint( $order_item->get_product_id() ) !== $product_id ) {
			wp_send_json_error( __( 'Product mismatch for selected order item.', 'claim-desk' ) );
		}

		$claimed_map    = $this->get_claimed_qty_map( $order_id );
		$item_total_qty = absint( $order_item->get_quantity() );
		$item_claimed   = isset( $claimed_map[ $order_item_id ] ) ? absint( $claimed_map[ $order_item_id ] ) : 0;
		$item_available = max( 0, $item_total_qty - $item_claimed );

		if ( $quantity > $item_available ) {
			wp_send_json_error( __( 'Selected quantity exceeds available claim quantity.', 'claim-desk' ) );
		}

		$db = new Claim_Desk_DB_Handler();

		$claim_id = $db->create_claim(
			array(
				'order_id'  => $order_id,
				'user_id'   => get_current_user_id(),
				'type_slug' => $claim_type,
				'status'    => 'pending',
			)
		);

		if ( ! $claim_id ) {
			wp_send_json_error( __( 'Failed to create claim.', 'claim-desk' ) );
		}

		$dynamic_data = array(
			'description'       => $description,
			'product_condition' => $product_condition,
			'refund_method'     => $refund_method,
		);

		$item_created = $db->create_claim_item(
			array(
				'claim_id'      => $claim_id,
				'order_item_id' => $order_item_id,
				'product_id'    => $product_id,
				'qty_total'     => $item_total_qty,
				'qty_claimed'   => $quantity,
				'reason_slug'   => $problem_type,
				'dynamic_data'  => wp_json_encode( $dynamic_data ),
			)
		);

		if ( ! $item_created ) {
			wp_send_json_error( __( 'Failed to save claim item.', 'claim-desk' ) );
		}

		$upload_result = $this->save_claim_uploads( $claim_id, $db );
		if ( is_wp_error( $upload_result ) ) {
			wp_send_json_error( $upload_result->get_error_message() );
		}

		wp_send_json_success(
			array(
				'message'  => __( 'Claim submitted successfully.', 'claim-desk' ),
				'claim_id' => $claim_id,
			)
		);
	}

	/**
	 * Save claim attachment uploads using WordPress media handling.
	 *
	 * @param int                   $claim_id Claim ID.
	 * @param Claim_Desk_DB_Handler $db DB handler.
	 * @return true|WP_Error
	 */
	private function save_claim_uploads( $claim_id, $db ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $_FILES['files'] ) || empty( $_FILES['files']['name'] ) || ! is_array( $_FILES['files']['name'] ) ) {
			return true;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$files = $_FILES['files'];
		$count = count( $files['name'] );

		if ( $count > 5 ) {
			return new WP_Error( 'cd_max_files', __( 'Maximum 5 images are allowed.', 'claim-desk' ) );
		}

		$allowed_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
		$allowed_mimes      = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		$upload_dir         = wp_upload_dir();

		for ( $i = 0; $i < $count; $i++ ) {
			$name = isset( $files['name'][ $i ] ) ? $files['name'][ $i ] : '';
			if ( '' === $name ) {
				continue;
			}

			$tmp_name = isset( $files['tmp_name'][ $i ] ) ? $files['tmp_name'][ $i ] : '';
			$size     = isset( $files['size'][ $i ] ) ? absint( $files['size'][ $i ] ) : 0;

			if ( $size > 2097152 ) {
				return new WP_Error( 'cd_file_size', __( 'Each image must be 2MB or smaller.', 'claim-desk' ) );
			}

			$filetype = wp_check_filetype_and_ext( $tmp_name, $name );
			$ext      = isset( $filetype['ext'] ) ? strtolower( (string) $filetype['ext'] ) : '';
			$mime     = isset( $filetype['type'] ) ? strtolower( (string) $filetype['type'] ) : '';

			if ( ! in_array( $ext, $allowed_extensions, true ) || ! in_array( $mime, $allowed_mimes, true ) ) {
				return new WP_Error( 'cd_file_type', __( 'Only JPG, PNG, GIF, and WEBP images are allowed.', 'claim-desk' ) );
			}

			$file = array(
				'name'     => $name,
				'type'     => isset( $files['type'][ $i ] ) ? $files['type'][ $i ] : '',
				'tmp_name' => $tmp_name,
				'error'    => isset( $files['error'][ $i ] ) ? $files['error'][ $i ] : 0,
				'size'     => $size,
			);

			$uploaded = wp_handle_upload(
				$file,
				array(
					'test_form' => false,
					'mimes'     => array(
						'jpg|jpeg|jpe' => 'image/jpeg',
						'png'          => 'image/png',
						'gif'          => 'image/gif',
						'webp'         => 'image/webp',
					),
				)
			);

			if ( isset( $uploaded['error'] ) ) {
				return new WP_Error( 'cd_upload_error', sanitize_text_field( $uploaded['error'] ) );
			}

			$attachment_id = wp_insert_attachment(
				array(
					'post_mime_type' => $uploaded['type'],
					'post_title'     => sanitize_file_name( wp_basename( $uploaded['file'] ) ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				),
				$uploaded['file']
			);

			if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
				return new WP_Error( 'cd_attachment_error', __( 'Failed to save uploaded image.', 'claim-desk' ) );
			}

			$attach_data = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
			if ( ! empty( $attach_data ) ) {
				wp_update_attachment_metadata( $attachment_id, $attach_data );
			}

			$relative_path = str_replace( $upload_dir['basedir'], '', $uploaded['file'] );

			$db->save_attachment(
				array(
					'claim_id'  => $claim_id,
					'file_path' => $relative_path,
					'file_name' => wp_basename( $uploaded['file'] ),
					'file_type' => $uploaded['type'],
					'file_size' => filesize( $uploaded['file'] ),
				)
			);
		}

		return true;
	}

	/**
	 * Build claimed quantity map by order item ID.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	private function get_claimed_qty_map( $order_id ) {
		$db           = new Claim_Desk_DB_Handler();
		$claimed_rows = $db->get_claimed_items_by_order( $order_id );
		$claimed_map  = array();

		if ( empty( $claimed_rows ) ) {
			return $claimed_map;
		}

		foreach ( $claimed_rows as $claimed_row ) {
			$key = absint( $claimed_row->order_item_id );
			if ( ! isset( $claimed_map[ $key ] ) ) {
				$claimed_map[ $key ] = 0;
			}
			$claimed_map[ $key ] += absint( $claimed_row->qty_claimed );
		}

		return $claimed_map;
	}

	/**
	 * Build latest claim status map by order item ID.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	private function get_claim_status_map( $order_id ) {
		global $wpdb;

		$claims_table = $wpdb->prefix . 'cd_claims';
		$items_table  = $wpdb->prefix . 'cd_claim_items';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT i.order_item_id, c.status, c.updated_at, c.id
				FROM {$items_table} i
				INNER JOIN {$claims_table} c ON c.id = i.claim_id
				WHERE c.order_id = %d
				ORDER BY c.updated_at DESC, c.id DESC",
				absint( $order_id )
			)
		);

		$status_map = array();
		if ( empty( $rows ) ) {
			return $status_map;
		}

		foreach ( $rows as $row ) {
			$order_item_id = absint( $row->order_item_id );
			if ( isset( $status_map[ $order_item_id ] ) ) {
				continue;
			}

			$status = sanitize_key( $row->status );
			if ( ! in_array( $status, array( 'pending', 'approved', 'rejected' ), true ) ) {
				$status = '';
			}

			$status_map[ $order_item_id ] = $status;
		}

		return $status_map;
	}

	/**
	 * Evaluate if claims are allowed for the order based on claim window config.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private function get_order_claim_window_status( $order ) {
		$order_status_check = $this->get_order_claim_status_check( $order );
		if ( empty( $order_status_check['allowed'] ) ) {
			return array(
				'allowed' => false,
				'mode'    => 'order_status',
				'days'    => 0,
				'message' => isset( $order_status_check['message'] ) ? $order_status_check['message'] : __( 'Claims can be created only after the order is delivered.', 'claim-desk' ),
			);
		}

		$settings = Claim_Desk_Config_Manager::get_claim_window();
		$mode     = isset( $settings['mode'] ) ? sanitize_key( $settings['mode'] ) : 'limited_days';
		$days     = isset( $settings['days'] ) ? absint( $settings['days'] ) : 30;

		if ( ! in_array( $mode, array( 'limited_days', 'no_limit', 'not_allowed' ), true ) ) {
			$mode = 'limited_days';
		}

		if ( $days < 1 ) {
			$days = 1;
		}

		if ( 'not_allowed' === $mode ) {
			return array(
				'allowed' => false,
				'mode'    => $mode,
				'days'    => $days,
				'message' => __( 'Claims are currently not allowed.', 'claim-desk' ),
			);
		}

		if ( 'no_limit' === $mode ) {
			return array(
				'allowed' => true,
				'mode'    => $mode,
				'days'    => $days,
				'message' => '',
			);
		}

		$completed_date = $order->get_date_completed();
		if ( ! $completed_date ) {
			return array(
				'allowed' => false,
				'mode'    => $mode,
				'days'    => $days,
				'message' => __( 'Claims are available after order completion.', 'claim-desk' ),
			);
		}

		$completed_ts = absint( $completed_date->getTimestamp() );
		$expiry_ts    = strtotime( '+' . $days . ' days', $completed_ts );
		$current_ts   = current_time( 'timestamp', true );

		if ( $expiry_ts && $current_ts > $expiry_ts ) {
			return array(
				'allowed' => false,
				'mode'    => $mode,
				'days'    => $days,
				'message' => sprintf(
					/* translators: %d: claim window days */
					__( 'Claim window expired. Claims are allowed within %d days after order completion.', 'claim-desk' ),
					$days
				),
			);
		}

		return array(
			'allowed' => true,
			'mode'    => $mode,
			'days'    => $days,
			'message' => '',
		);
	}

	/**
	 * Check whether order status allows claim creation.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private function get_order_claim_status_check( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return array(
				'allowed' => false,
				'message' => __( 'Claims can be created only after the order is delivered.', 'claim-desk' ),
			);
		}

		$status = sanitize_key( $order->get_status() );
		$is_allowed = ( 'completed' === $status );
		$message    = '';

		if ( ! $is_allowed ) {
			switch ( $status ) {
				case 'pending':
				case 'pending-payment':
					$message = __( 'Your order is awaiting payment. Claims can be created after the order is paid and delivered.', 'claim-desk' );
					break;
				case 'processing':
					$message = __( 'Your order is being prepared for shipment. Claims can be created after delivery.', 'claim-desk' );
					break;
				case 'on-hold':
					$message = __( 'Your order is currently on hold. Claims will be available once the order is processed and delivered.', 'claim-desk' );
					break;
				case 'cancelled':
					$message = __( 'This order has been cancelled. Claims are not available for cancelled orders.', 'claim-desk' );
					break;
				case 'refunded':
					$message = __( 'This order has already been refunded. No further claims can be created.', 'claim-desk' );
					break;
				case 'failed':
					$message = __( 'This order was not successfully processed. Claims are not available for failed orders.', 'claim-desk' );
					break;
				case 'draft':
					$message = __( 'This order is still in draft state. Claims will be available once the order is completed.', 'claim-desk' );
					break;
				default:
					$message = __( 'Claims can be created only after the order is delivered.', 'claim-desk' );
					break;
			}
		}

		return array(
			'allowed' => $is_allowed,
			'message' => $message,
		);
	}
}
