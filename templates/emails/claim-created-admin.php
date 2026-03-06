<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
?>
<p><?php esc_html_e( 'A new claim has been created and is awaiting review.', 'claim-desk' ); ?></p>
<?php wc_get_template( 'emails/claim-email-details.php', array( 'claim' => $claim ), 'claim-desk/', trailingslashit( CLAIM_DESK_PLUGIN_PATH ) . 'templates/' ); ?>
<?php
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
