<?php

/**
 * Manages the configuration (Scopes, Reasons, Fields) for the plugin.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Claim_Desk_Config_Manager {

    /**
     * Option name for storing scopes.
     */
    const OPTION_SCOPES = 'claim_desk_scopes';

    /**
     * Initialize hooks.
     */
    public function init() {
        add_action( 'wp_ajax_claim_desk_save_config', array( $this, 'ajax_save_config' ) );
        add_action( 'wp_ajax_claim_desk_get_config', array( $this, 'ajax_get_config' ) );
    }

    /**
     * Get all configured scopes.
     * 
     * Structure:
     * [
     *   'quality' => [
     *      'label' => 'Product Quality',
     *      'icon' => 'box',
     *      'reasons' => ['taste', 'mold', 'damaged'],
     *      'fields' => [
     *          ['id' => 'batch_no', 'type' => 'text', 'label' => 'Batch Number']
     *      ]
     *   ]
     * ]
     */
    public static function get_scopes() {
        // Legacy support
        return get_option( self::OPTION_SCOPES, array() );
    }

    /**
     * Get configured resolutions (Return, Exchange, Coupon).
     */
    public static function get_resolutions() {
        $defaults = array(
            'return'   => true,
            'exchange' => true,
            'coupon'   => true
        );
        return get_option( 'claim_desk_resolutions', $defaults );
    }

    /**
     * Get configured problem types.
     */
    public static function get_problems() {
        $defaults = array(
            array( 'value' => 'damaged', 'label' => esc_html__( 'Product Damaged', 'claim-desk' ) ),
            array( 'value' => 'defective', 'label' => esc_html__( 'Product Defective', 'claim-desk' ) ),
            array( 'value' => 'wrong-item', 'label' => esc_html__( 'Wrong Item Received', 'claim-desk' ) ),
            array( 'value' => 'wrong-size', 'label' => esc_html__( 'Wrong Size/Color', 'claim-desk' ) ),
            array( 'value' => 'not-as-described', 'label' => esc_html__( 'Not As Described', 'claim-desk' ) ),
            array( 'value' => 'quality-issue', 'label' => esc_html__( 'Quality Issue', 'claim-desk' ) ),
            array( 'value' => 'other', 'label' => esc_html__( 'Other', 'claim-desk' ) )
        );
        return get_option( 'claim_desk_problems', $defaults );
    }

    /**
     * Get configured product conditions.
     */
    public static function get_conditions() {
        $defaults = array(
            array( 'value' => 'unopened', 'label' => esc_html__( 'Unopened', 'claim-desk' ) ),
            array( 'value' => 'opened', 'label' => esc_html__( 'Opened', 'claim-desk' ) ),
            array( 'value' => 'damaged', 'label' => esc_html__( 'Damaged', 'claim-desk' ) )
        );
        return get_option( 'claim_desk_conditions', $defaults );
    }

    /**
     * Get claim window settings.
     *
     * @return array
     */
    public static function get_claim_window() {
        $defaults = array(
            'mode' => 'limited_days',
            'days' => 30,
        );

        $settings = get_option( 'claim_desk_claim_window', $defaults );
        if ( ! is_array( $settings ) ) {
            return $defaults;
        }

        $mode = isset( $settings['mode'] ) ? sanitize_key( $settings['mode'] ) : $defaults['mode'];
        if ( ! in_array( $mode, array( 'limited_days', 'no_limit', 'not_allowed' ), true ) ) {
            $mode = $defaults['mode'];
        }

        $days = isset( $settings['days'] ) ? absint( $settings['days'] ) : $defaults['days'];
        if ( $days < 1 ) {
            $days = $defaults['days'];
        }

        return array(
            'mode' => $mode,
            'days' => $days,
        );
    }

    /**
     * AJAX Handler: Save Configuration.
     */
    public function ajax_save_config() {
        check_ajax_referer( 'claim_desk_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'claim-desk' ) );
        }

        // Save Scopes (Legacy)
        if ( isset( $_POST['scopes'] ) ) {
            // Read raw JSON safely, then sanitize decoded structure below.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $scopes_raw = (string) wp_unslash( $_POST['scopes'] );
            $scopes = json_decode( $scopes_raw, true );
            if ( is_array( $scopes ) ) {
                 // Sanitize legacy scopes structure
                 $clean_scopes = array();
                 foreach ( $scopes as $scope ) {
                     $slug = sanitize_key( $scope['slug'] );
                     $clean_scopes[ $slug ] = array(
                         'slug'    => $slug,
                         'label'   => sanitize_text_field( $scope['label'] ),
                         'icon'    => sanitize_html_class( $scope['icon'] ),
                         'reasons' => isset($scope['reasons']) ? array_map(function($r){
                             return array('slug'=>sanitize_key($r['slug']), 'label'=>sanitize_text_field($r['label']));
                         }, $scope['reasons']) : [],
                         'fields'  => isset($scope['fields']) ? array_map(function($f){
                             return array(
                                 'slug'=>sanitize_key($f['slug']), 
                                 'label'=>sanitize_text_field($f['label']),
                                 'type'=>sanitize_key($f['type']),
                                 'required'=>!empty($f['required'])
                             );
                         }, $scope['fields']) : []
                     );
                 }
                 update_option( self::OPTION_SCOPES, $clean_scopes );
            }
        }

        // Save Resolutions
        if ( isset( $_POST['resolutions'] ) ) {
            // Read raw array safely, then normalize values.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $resolutions_raw = wp_unslash( $_POST['resolutions'] );

            $resolutions = array();
            if ( is_array( $resolutions_raw ) ) {
                $resolutions = array_map(
                    static function ( $val ) {
                        $val = sanitize_text_field( (string) $val );
                        return ( 'true' === $val || '1' === $val );
                    },
                    $resolutions_raw
                );
            }
            update_option( 'claim_desk_resolutions', $resolutions );
        }

        // Save Problems
        if ( isset( $_POST['problems'] ) ) {
            // Read raw JSON safely, then sanitize decoded structure below.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $problems_raw = (string) wp_unslash( $_POST['problems'] );
            $problems     = json_decode( $problems_raw, true );
            if ( is_array( $problems ) ) {
                $clean_problems = array_map( function($p) {
                    return array(
                        'value' => sanitize_title( $p['value'] ),
                        'label' => sanitize_text_field( $p['label'] )
                    );
                }, $problems );
                update_option( 'claim_desk_problems', $clean_problems );
            }
        }

        // Save Conditions
        if ( isset( $_POST['conditions'] ) ) {
            // Read raw JSON safely, then sanitize decoded structure below.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $conditions_raw = (string) wp_unslash( $_POST['conditions'] );
            $conditions     = json_decode( $conditions_raw, true );
            if ( is_array( $conditions ) ) {
                $clean_conditions = array_map( function($c) {
                    return array(
                        'value' => sanitize_title( $c['value'] ),
                        'label' => sanitize_text_field( $c['label'] )
                    );
                }, $conditions );
                update_option( 'claim_desk_conditions', $clean_conditions );
            }
        }

        // Save Claim Window
        if ( isset( $_POST['claim_window'] ) ) {
            // Read raw array safely, then sanitize values below.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $claim_window_raw = wp_unslash( $_POST['claim_window'] );
            $claim_window     = array(
                'mode' => 'limited_days',
                'days' => 30,
            );

            if ( is_array( $claim_window_raw ) ) {
                $mode = isset( $claim_window_raw['mode'] ) ? sanitize_key( (string) $claim_window_raw['mode'] ) : 'limited_days';
                if ( ! in_array( $mode, array( 'limited_days', 'no_limit', 'not_allowed' ), true ) ) {
                    $mode = 'limited_days';
                }

                $days = isset( $claim_window_raw['days'] ) ? absint( $claim_window_raw['days'] ) : 30;
                if ( $days < 1 ) {
                    $days = 1;
                }

                $claim_window['mode'] = $mode;
                $claim_window['days'] = $days;
            }

            update_option( 'claim_desk_claim_window', $claim_window );
        }

        wp_send_json_success( __( 'Configuration saved.', 'claim-desk' ) );
    }

    /**
     * AJAX Handler: Get Configuration.
     */
    public function ajax_get_config() {
        check_ajax_referer( 'claim_desk_admin_nonce', 'nonce' );
        
        $data = array(
            'scopes'      => self::get_scopes(),
            'resolutions' => self::get_resolutions(),
            'problems'    => self::get_problems(),
            'conditions'  => self::get_conditions(),
            'claim_window' => self::get_claim_window(),
        );

        wp_send_json_success( $data );
    }

}
