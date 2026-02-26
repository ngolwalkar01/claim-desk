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
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/claim-desk-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Enqueue public scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/claim-desk-public.js', array( 'jquery' ), $this->version, true );

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
		$actions['claim-desk-trigger'] = array(
			'url'    => $order->get_view_order_url() . '#cd-order-claims',
			'name'   => __( 'Start Claim', 'claim-desk' ),
			'action' => 'claim-desk-trigger',
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

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found.', 'claim-desk' ) );
		}

		if ( $order->get_user_id() !== get_current_user_id() && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'claim-desk' ) );
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
			'description'   => $description,
			'refund_method' => $refund_method,
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
}
