<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo wp_kses_post( $email_heading . "\n\n" );
echo "No action has been taken on this claim within the configured reminder period.\n\n";
wc_get_template( 'emails/plain/claim-email-details.php', array( 'claim' => $claim ), 'claim-desk/', trailingslashit( CLAIM_DESK_PLUGIN_PATH ) . 'templates/' );
echo wp_kses_post( sprintf( "\nOpen claim in admin: %s\n", esc_url_raw( $claim['admin_claim_url'] ) ) );

