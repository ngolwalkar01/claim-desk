<?php
/**
 * Claim created - admin email.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes/emails
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once CLAIM_DESK_PLUGIN_PATH . 'includes/emails/class-claim-desk-email-base.php';

class Claim_Desk_Email_Claim_Created_Admin extends Claim_Desk_Email_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'claim_desk_claim_created_admin';
		$this->title          = __( 'Claim Created - Admin', 'claim-desk' );
		$this->description    = __( 'Sent to admin when a claim is created.', 'claim-desk' );
		$this->customer_email = false;
		$this->template_html  = 'emails/claim-created-admin.php';
		$this->template_plain = 'emails/plain/claim-created-admin.php';
		$this->template_base  = trailingslashit( CLAIM_DESK_PLUGIN_PATH ) . 'templates/';
		$this->placeholders   = array(
			'{claim_id}'     => '',
			'{order_number}' => '',
			'{status}'       => '',
			'{claim_type}'   => '',
		);
		$this->recipient      = get_option( 'admin_email' );

		add_action( 'claim_desk_trigger_claim_created_admin_email', array( $this, 'trigger' ), 10, 1 );
		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}] New claim #{claim_id} created for order #{order_number}', 'claim-desk' );
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'New Claim Created', 'claim-desk' );
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
