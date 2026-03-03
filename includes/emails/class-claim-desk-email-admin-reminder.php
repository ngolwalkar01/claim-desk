<?php
/**
 * Admin reminder - no action taken.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes/emails
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once CLAIM_DESK_PLUGIN_PATH . 'includes/emails/class-claim-desk-email-base.php';

class Claim_Desk_Email_Admin_Reminder extends Claim_Desk_Email_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'claim_desk_admin_reminder';
		$this->title          = __( 'Admin Reminder - No Action Taken', 'claim-desk' );
		$this->description    = __( 'Sent to admin if no action is taken on a claim after the configured delay.', 'claim-desk' );
		$this->customer_email = false;
		$this->template_html  = 'emails/admin-reminder.php';
		$this->template_plain = 'emails/plain/admin-reminder.php';
		$this->template_base  = trailingslashit( CLAIM_DESK_PLUGIN_PATH ) . 'templates/';
		$this->placeholders   = array(
			'{claim_id}'     => '',
			'{order_number}' => '',
			'{status}'       => '',
			'{claim_type}'   => '',
		);
		$this->recipient      = get_option( 'admin_email' );

		add_action( 'claim_desk_trigger_admin_reminder_email', array( $this, 'trigger' ), 10, 1 );
		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}] Reminder: claim #{claim_id} for order #{order_number} needs action', 'claim-desk' );
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Claim Pending Action Reminder', 'claim-desk' );
	}

	/**
	 * Trigger email.
	 *
	 * @param int $claim_id Claim ID.
	 * @return void
	 */
	public function trigger( $claim_id ) {
		if ( ! $this->setup_claim( absint( $claim_id ) ) ) {
			return;
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}
}
