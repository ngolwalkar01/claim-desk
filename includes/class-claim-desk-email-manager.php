<?php
/**
 * Claim Desk email registration and triggers.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Claim_Desk_Email_Manager {

	/**
	 * Load and register custom email classes.
	 *
	 * @param array $emails WooCommerce emails.
	 * @return array
	 */
	public function register_email_classes( $emails ) {
		if ( ! class_exists( 'WC_Email' ) ) {
			return $emails;
		}

		require_once CLAIM_DESK_PLUGIN_PATH . 'includes/emails/class-claim-desk-email-claim-created-customer.php';
		require_once CLAIM_DESK_PLUGIN_PATH . 'includes/emails/class-claim-desk-email-claim-created-admin.php';
		require_once CLAIM_DESK_PLUGIN_PATH . 'includes/emails/class-claim-desk-email-claim-status-updated-customer.php';
		require_once CLAIM_DESK_PLUGIN_PATH . 'includes/emails/class-claim-desk-email-admin-reminder.php';

		$emails['claim_desk_claim_created_customer']       = new Claim_Desk_Email_Claim_Created_Customer();
		$emails['claim_desk_claim_created_admin']          = new Claim_Desk_Email_Claim_Created_Admin();
		$emails['claim_desk_claim_status_updated_customer']= new Claim_Desk_Email_Claim_Status_Updated_Customer();
		$emails['claim_desk_admin_reminder']               = new Claim_Desk_Email_Admin_Reminder();

		return $emails;
	}

	/**
	 * Trigger customer+admin claim-created emails.
	 *
	 * @param int $claim_id Claim ID.
	 * @return void
	 */
	public function trigger_claim_created_emails( $claim_id ) {
		if ( function_exists( 'WC' ) ) {
			WC()->mailer();
		}
		do_action( 'claim_desk_trigger_claim_created_customer_email', absint( $claim_id ) );
		do_action( 'claim_desk_trigger_claim_created_admin_email', absint( $claim_id ) );
	}

	/**
	 * Trigger customer status-updated email.
	 *
	 * @param int    $claim_id Claim ID.
	 * @param string $new_status New status.
	 * @return void
	 */
	public function trigger_claim_status_updated_email( $claim_id, $new_status ) {
		if ( function_exists( 'WC' ) ) {
			WC()->mailer();
		}
		do_action( 'claim_desk_trigger_claim_status_updated_customer_email', absint( $claim_id ), sanitize_key( $new_status ) );
	}
}
