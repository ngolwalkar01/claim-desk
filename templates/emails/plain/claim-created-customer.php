<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo wp_kses_post( $email_heading . "\n\n" );
echo "Your claim has been submitted successfully.\n\n";
wc_get_template( 'emails/plain/claim-email-details.php', array( 'claim' => $claim ), 'claim-desk/', trailingslashit( CLAIM_DESK_PLUGIN_PATH ) . 'templates/' );

