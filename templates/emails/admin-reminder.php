<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
?>
<p><?php esc_html_e( 'No action has been taken on this claim within the configured reminder period.', 'claim-desk' ); ?></p>
<?php wc_get_template( 'emails/claim-email-details.php', array( 'claim' => $claim ), 'claim-desk/', trailingslashit( CLAIM_DESK_PLUGIN_PATH ) . 'templates/' ); ?>
<p>
	<a href="<?php echo esc_url( $claim['admin_claim_url'] ); ?>"><?php esc_html_e( 'Open claim in admin', 'claim-desk' ); ?></a>
</p>
<?php
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
