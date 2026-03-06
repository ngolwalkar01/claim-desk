<?php
/**
 * Shared claim details table for HTML emails.
 *
 * @var array $claim
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2><?php esc_html_e( 'Claim Details', 'claim-desk' ); ?></h2>
<table cellspacing="0" cellpadding="6" border="1" style="width:100%; border-collapse:collapse;">
	<tbody>
		<tr><th align="left"><?php esc_html_e( 'Claim ID', 'claim-desk' ); ?></th><td><?php echo esc_html( absint( $claim['id'] ) ); ?></td></tr>
		<tr><th align="left"><?php esc_html_e( 'Order number', 'claim-desk' ); ?></th><td><?php echo esc_html( (string) $claim['order_number'] ); ?></td></tr>
		<tr><th align="left"><?php esc_html_e( 'Claim type', 'claim-desk' ); ?></th><td><?php echo esc_html( ucfirst( (string) $claim['type_slug'] ) ); ?></td></tr>
		<tr><th align="left"><?php esc_html_e( 'Claim reason', 'claim-desk' ); ?></th><td><?php echo esc_html( (string) $claim['reason_slug'] ); ?></td></tr>
		<tr><th align="left"><?php esc_html_e( 'Claim description', 'claim-desk' ); ?></th><td><?php echo esc_html( (string) $claim['description'] ); ?></td></tr>
		<tr><th align="left"><?php esc_html_e( 'Quantity', 'claim-desk' ); ?></th><td><?php echo esc_html( absint( $claim['total_quantity'] ) ); ?></td></tr>
		<tr><th align="left"><?php esc_html_e( 'Submission date', 'claim-desk' ); ?></th><td><?php echo esc_html( (string) $claim['created_at'] ); ?></td></tr>
		<tr><th align="left"><?php esc_html_e( 'Current status', 'claim-desk' ); ?></th><td><?php echo esc_html( ucfirst( (string) $claim['status'] ) ); ?></td></tr>
		<tr><th align="left"><?php esc_html_e( 'Admin note', 'claim-desk' ); ?></th><td><?php echo ! empty( $claim['admin_remarks'] ) ? esc_html( (string) $claim['admin_remarks'] ) : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr>
	</tbody>
</table>

<h2><?php esc_html_e( 'Products', 'claim-desk' ); ?></h2>
<table cellspacing="0" cellpadding="6" border="1" style="width:100%; border-collapse:collapse;">
	<thead>
		<tr>
			<th align="left"><?php esc_html_e( 'Image', 'claim-desk' ); ?></th>
			<th align="left"><?php esc_html_e( 'Product', 'claim-desk' ); ?></th>
			<th align="left"><?php esc_html_e( 'Claimed quantity', 'claim-desk' ); ?></th>
			<th align="left"><?php esc_html_e( 'SKU', 'claim-desk' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $claim['items'] as $claim_desk_item ) : ?>
			<tr>
				<td><?php echo wp_kses_post( $claim_desk_item['product_image'] ); ?></td>
				<td>
					<?php if ( ! empty( $claim_desk_item['product_url'] ) ) : ?>
						<a href="<?php echo esc_url( $claim_desk_item['product_url'] ); ?>"><?php echo esc_html( $claim_desk_item['product_name'] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $claim_desk_item['product_name'] ); ?>
					<?php endif; ?>
				</td>
				<td><?php echo esc_html( absint( $claim_desk_item['qty_claimed'] ) ); ?></td>
				<td><?php echo ! empty( $claim_desk_item['product_sku'] ) ? esc_html( $claim_desk_item['product_sku'] ) : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
