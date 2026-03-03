<?php
/**
 * Claim reminder scheduler and processor.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Claim_Desk_Reminder_Service {

	/**
	 * Ensure cron event exists.
	 *
	 * @return void
	 */
	public function maybe_schedule_event() {
		if ( ! wp_next_scheduled( 'claim_desk_check_reminders' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'claim_desk_check_reminders' );
		}
	}

	/**
	 * Update reminder tracking when a claim is created.
	 *
	 * @param int $claim_id Claim ID.
	 * @return void
	 */
	public function handle_claim_created( $claim_id ) {
		$claim_id = absint( $claim_id );
		if ( ! $claim_id ) {
			return;
		}

		$db = new Claim_Desk_DB_Handler();
		$db->reset_reminder_tracking( $claim_id );
	}

	/**
	 * Update reminder tracking when claim status changes.
	 *
	 * @param int    $claim_id Claim ID.
	 * @param string $new_status New status.
	 * @return void
	 */
	public function handle_claim_status_updated( $claim_id, $new_status ) {
		$claim_id    = absint( $claim_id );
		$new_status  = sanitize_key( $new_status );
		$terminal_states = array( 'approved', 'rejected', 'completed', 'closed' );
		if ( ! $claim_id ) {
			return;
		}

		$db = new Claim_Desk_DB_Handler();
		if ( in_array( $new_status, $terminal_states, true ) ) {
			$db->mark_reminder_not_required( $claim_id );
			return;
		}

		$db->reset_reminder_tracking( $claim_id );
	}

	/**
	 * Periodic cron processor for reminders.
	 *
	 * @return void
	 */
	public function process_pending_claims() {
		$settings = Claim_Desk_Config_Manager::get_reminder_settings();
		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		$delay_days = isset( $settings['delay_days'] ) ? absint( $settings['delay_days'] ) : 0;
		if ( $delay_days < 1 ) {
			return;
		}

		$threshold_mysql = wp_date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( DAY_IN_SECONDS * $delay_days ) );
		$db              = new Claim_Desk_DB_Handler();
		$claim_ids       = $db->get_claim_ids_for_reminder( $threshold_mysql, 100 );

		if ( empty( $claim_ids ) ) {
			return;
		}

		foreach ( $claim_ids as $claim_id ) {
			if ( function_exists( 'WC' ) ) {
				WC()->mailer();
			}
			do_action( 'claim_desk_trigger_admin_reminder_email', absint( $claim_id ) );
			$db->mark_reminder_sent( absint( $claim_id ) );
		}
	}
}
