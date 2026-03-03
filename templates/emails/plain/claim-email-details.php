<?php
/**
 * Shared claim details for plain emails.
 *
 * @var array $claim
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo wp_kses_post( sprintf( "Claim ID: %d\n", absint( $claim['id'] ) ) );
echo wp_kses_post( sprintf( "Order number: %s\n", sanitize_text_field( (string) $claim['order_number'] ) ) );
echo wp_kses_post( sprintf( "Claim type: %s\n", sanitize_text_field( ucfirst( (string) $claim['type_slug'] ) ) ) );
echo wp_kses_post( sprintf( "Claim reason: %s\n", sanitize_text_field( (string) $claim['reason_slug'] ) ) );
echo wp_kses_post( sprintf( "Claim description: %s\n", sanitize_textarea_field( (string) $claim['description'] ) ) );
echo wp_kses_post( sprintf( "Quantity: %d\n", absint( $claim['total_quantity'] ) ) );
echo wp_kses_post( sprintf( "Submission date: %s\n", sanitize_text_field( (string) $claim['created_at'] ) ) );
echo wp_kses_post( sprintf( "Current status: %s\n", sanitize_text_field( ucfirst( (string) $claim['status'] ) ) ) );
echo wp_kses_post( sprintf( "Admin note: %s\n\n", ! empty( $claim['admin_remarks'] ) ? sanitize_textarea_field( (string) $claim['admin_remarks'] ) : '-' ) );

echo "Products:\n";
foreach ( $claim['items'] as $item ) {
	echo wp_kses_post( sprintf( "- Product: %s\n", sanitize_text_field( (string) $item['product_name'] ) ) );
	echo wp_kses_post( sprintf( "  Link: %s\n", esc_url_raw( (string) $item['product_url'] ) ) );
	echo wp_kses_post( sprintf( "  Claimed quantity: %d\n", absint( $item['qty_claimed'] ) ) );
	echo wp_kses_post( sprintf( "  SKU: %s\n", ! empty( $item['product_sku'] ) ? sanitize_text_field( (string) $item['product_sku'] ) : '-' ) );
}
