<?php
/**
 * Base WC_Email class for Claim Desk emails.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes/emails
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

abstract class Claim_Desk_Email_Base extends WC_Email {

	/**
	 * Claim payload.
	 *
	 * @var array
	 */
	protected $claim = array();

	/**
	 * Load claim and placeholders.
	 *
	 * @param int $claim_id Claim ID.
	 * @return bool
	 */
	protected function setup_claim( $claim_id ) {
		$this->claim = Claim_Desk_Claim_Data::get_claim_email_data( $claim_id );
		if ( empty( $this->claim ) ) {
			return false;
		}

		$this->placeholders = array_merge(
			(array) $this->placeholders,
			Claim_Desk_Claim_Data::get_placeholders( $this->claim )
		);

		return true;
	}

	/**
	 * Get subject with placeholders.
	 *
	 * @return string
	 */
	public function get_subject() {
		return $this->format_string( parent::get_subject() );
	}

	/**
	 * Get heading with placeholders.
	 *
	 * @return string
	 */
	public function get_heading() {
		return $this->format_string( parent::get_heading() );
	}

	/**
	 * Get HTML content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'claim'            => $this->claim,
				'email_heading'    => $this->get_heading(),
				'sent_to_admin'    => ! $this->customer_email,
				'plain_text'       => false,
				'email'            => $this,
				'additional_content' => $this->get_additional_content(),
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Get plain content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'claim'            => $this->claim,
				'email_heading'    => $this->get_heading(),
				'sent_to_admin'    => ! $this->customer_email,
				'plain_text'       => true,
				'email'            => $this,
				'additional_content' => $this->get_additional_content(),
			),
			'',
			$this->template_base
		);
	}
}
