<?php
/**
 * Claim status updated - customer email.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes/emails
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once CLAIM_DESK_PLUGIN_PATH . 'includes/emails/class-claim-desk-email-base.php';

class Claim_Desk_Email_Claim_Status_Updated_Customer extends Claim_Desk_Email_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'claim_desk_claim_status_updated_customer';
		$this->title          = __( 'Claim Status Updated - Customer', 'claim-desk' );
		$this->description    = __( 'Sent to customer when claim status is updated.', 'claim-desk' );
		$this->customer_email = true;
		$this->template_html  = 'emails/claim-status-updated-customer.php';
		$this->template_plain = 'emails/plain/claim-status-updated-customer.php';
		$this->template_base  = trailingslashit( CLAIM_DESK_PLUGIN_PATH ) . 'templates/';
		$this->placeholders   = array(
			'{claim_id}'     => '',
			'{order_number}' => '',
			'{status}'       => '',
			'{claim_type}'   => '',
		);

		add_action( 'claim_desk_trigger_claim_status_updated_customer_email', array( $this, 'trigger' ), 10, 2 );
		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Update: claim #{claim_id} for order #{order_number} is now {status}', 'claim-desk' );
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Claim Status Updated', 'claim-desk' );
	}

	/**
	 * Trigger email.
	 *
	 * @param int    $claim_id Claim ID.
	 * @param string $new_status New status.
	 * @return void
	 */
	public function trigger( $claim_id, $new_status = '' ) {
		if ( ! $this->setup_claim( absint( $claim_id ) ) ) {
			return;
		}

		if ( ! empty( $new_status ) ) {
			$this->claim['status']       = sanitize_key( $new_status );
			$this->placeholders['{status}'] = sanitize_key( $new_status );
		}

		$user            = get_userdata( absint( $this->claim['user_id'] ) );
		$this->recipient = ( $user && ! empty( $user->user_email ) ) ? $user->user_email : '';

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}
}
