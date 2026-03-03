<?php
/**
 * Claim created - customer email.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes/emails
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once CLAIM_DESK_PLUGIN_PATH . 'includes/emails/class-claim-desk-email-base.php';

class Claim_Desk_Email_Claim_Created_Customer extends Claim_Desk_Email_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'claim_desk_claim_created_customer';
		$this->title          = __( 'Claim Created - Customer', 'claim-desk' );
		$this->description    = __( 'Sent to customer when a claim is created.', 'claim-desk' );
		$this->customer_email = true;
		$this->template_html  = 'emails/claim-created-customer.php';
		$this->template_plain = 'emails/plain/claim-created-customer.php';
		$this->template_base  = trailingslashit( CLAIM_DESK_PLUGIN_PATH ) . 'templates/';
		$this->placeholders   = array(
			'{claim_id}'     => '',
			'{order_number}' => '',
			'{status}'       => '',
			'{claim_type}'   => '',
		);

		add_action( 'claim_desk_trigger_claim_created_customer_email', array( $this, 'trigger' ), 10, 1 );
		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Your claim #{claim_id} for order #{order_number} is received', 'claim-desk' );
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Claim Received', 'claim-desk' );
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

		$user            = get_userdata( absint( $this->claim['user_id'] ) );
		$this->recipient = ( $user && ! empty( $user->user_email ) ) ? $user->user_email : '';

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}
}
