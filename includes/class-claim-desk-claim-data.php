<?php
/**
 * Claim data helper for email payloads.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Claim_Desk_Claim_Data {

	/**
	 * Fetch claim data formatted for emails.
	 *
	 * @param int $claim_id Claim ID.
	 * @return array|null
	 */
	public static function get_claim_email_data( $claim_id ) {
		$db    = new Claim_Desk_DB_Handler();
		$claim = $db->get_claim( $claim_id );
		if ( empty( $claim ) ) {
			return null;
		}

		$items         = $db->get_claim_items( $claim_id );
		$order         = wc_get_order( absint( $claim->order_id ) );
		$order_number  = $order ? $order->get_order_number() : (string) absint( $claim->order_id );
		$product_items = array();
		$reasons       = array();
		$first_desc    = '';
		$total_qty     = 0;

		foreach ( $items as $item ) {
			$product = wc_get_product( absint( $item->product_id ) );
			$name    = $product ? $product->get_name() : __( 'Unknown Product', 'claim-desk' );
			$link    = $product ? $product->get_permalink() : '';
			$image   = $product ? $product->get_image( 'thumbnail' ) : wc_placeholder_img( 'thumbnail' );
			$sku     = $product ? (string) $product->get_sku() : '';

			$dynamic_data = json_decode( (string) $item->dynamic_data, true );
			if ( ! empty( $item->reason_slug ) ) {
				$reasons[] = sanitize_text_field( (string) $item->reason_slug );
			}
			if ( empty( $first_desc ) && is_array( $dynamic_data ) && ! empty( $dynamic_data['description'] ) ) {
				$first_desc = sanitize_textarea_field( (string) $dynamic_data['description'] );
			}

			$total_qty += absint( $item->qty_claimed );

			$product_items[] = array(
				'product_id'   => absint( $item->product_id ),
				'product_name' => $name,
				'product_url'  => $link,
				'product_sku'  => $sku,
				'product_image'=> $image,
				'qty_claimed'  => absint( $item->qty_claimed ),
				'reason_slug'  => sanitize_text_field( (string) $item->reason_slug ),
			);
		}

		$reason_text = implode( ', ', array_unique( array_filter( $reasons ) ) );

		return array(
			'id'              => absint( $claim->id ),
			'order_id'        => absint( $claim->order_id ),
			'order_number'    => sanitize_text_field( (string) $order_number ),
			'user_id'         => absint( $claim->user_id ),
			'type_slug'       => sanitize_text_field( (string) $claim->type_slug ),
			'status'          => sanitize_key( (string) $claim->status ),
			'admin_remarks'   => sanitize_textarea_field( (string) $claim->admin_remarks ),
			'created_at'      => sanitize_text_field( (string) $claim->created_at ),
			'updated_at'      => sanitize_text_field( (string) $claim->updated_at ),
			'reason_slug'     => $reason_text,
			'description'     => $first_desc,
			'total_quantity'  => absint( $total_qty ),
			'items'           => $product_items,
			'admin_claim_url' => self::get_admin_claim_url( absint( $claim->id ) ),
		);
	}

	/**
	 * Build placeholders.
	 *
	 * @param array $claim Claim data.
	 * @return array
	 */
	public static function get_placeholders( $claim ) {
		return array(
			'{claim_id}'     => isset( $claim['id'] ) ? (string) absint( $claim['id'] ) : '',
			'{order_number}' => isset( $claim['order_number'] ) ? sanitize_text_field( (string) $claim['order_number'] ) : '',
			'{status}'       => isset( $claim['status'] ) ? sanitize_text_field( $claim['status'] ) : '',
			'{claim_type}'   => isset( $claim['type_slug'] ) ? sanitize_text_field( $claim['type_slug'] ) : '',
		);
	}

	/**
	 * Admin claim edit URL.
	 *
	 * @param int $claim_id Claim ID.
	 * @return string
	 */
	private static function get_admin_claim_url( $claim_id ) {
		return add_query_arg(
			array(
				'page'     => 'claim-desk',
				'tab'      => 'claims',
				'action'   => 'view',
				'id'       => absint( $claim_id ),
				'_wpnonce' => wp_create_nonce( 'view_claim' ),
			),
			admin_url( 'admin.php' )
		);
	}
}
