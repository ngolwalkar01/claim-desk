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
     * Get reminder settings.
     *
     * @return array
     */
    public static function get_reminder_settings() {
        $defaults = array(
            'enabled' => false,
            'delay' => '3',
            'custom_days' => 3,
            'delay_days' => 3,
        );

        $settings = get_option( 'claim_desk_reminder_settings', $defaults );
        if ( ! is_array( $settings ) ) {
            return $defaults;
        }

        $enabled = ! empty( $settings['enabled'] );
        $delay   = isset( $settings['delay'] ) ? sanitize_key( (string) $settings['delay'] ) : '3';
        if ( ! in_array( $delay, array( '1', '2', '3', '7', 'custom' ), true ) ) {
            $delay = '3';
        }

        $custom_days = isset( $settings['custom_days'] ) ? absint( $settings['custom_days'] ) : 3;
        if ( $custom_days < 1 ) {
            $custom_days = 1;
        }

        $delay_days = ( 'custom' === $delay ) ? $custom_days : absint( $delay );
        if ( $delay_days < 1 ) {
            $delay_days = 3;
        }

        return array(
            'enabled' => $enabled,
            'delay' => $delay,
            'custom_days' => $custom_days,
            'delay_days' => $delay_days,
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
            // Read raw JSON safely, validate type, and sanitize every decoded value before use.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $scopes_input = wp_unslash( $_POST['scopes'] );
            if ( is_string( $scopes_input ) ) {
                $scopes_raw = $scopes_input;
                $scopes     = json_decode( $scopes_raw, true );

                if ( is_array( $scopes ) ) {
                    $clean_scopes = array();

                    foreach ( $scopes as $scope ) {
                        if ( ! is_array( $scope ) ) {
                            continue;
                        }

                        $scope_slug_raw  = isset( $scope['slug'] ) ? $scope['slug'] : '';
                        $scope_label_raw = isset( $scope['label'] ) ? $scope['label'] : '';
                        $scope_icon_raw  = isset( $scope['icon'] ) ? $scope['icon'] : '';

                        $slug  = sanitize_key( is_string( $scope_slug_raw ) ? $scope_slug_raw : '' );
                        $label = sanitize_text_field( is_string( $scope_label_raw ) ? $scope_label_raw : '' );
                        $icon  = sanitize_text_field( is_string( $scope_icon_raw ) ? $scope_icon_raw : '' );

                        $clean_scope = array(
                            'slug'    => $slug,
                            'label'   => $label,
                            'icon'    => $icon,
                            'reasons' => array(),
                            'fields'  => array(),
                        );

                        if ( isset( $scope['reasons'] ) && is_array( $scope['reasons'] ) ) {
                            foreach ( $scope['reasons'] as $reason ) {
                                if ( ! is_array( $reason ) ) {
                                    continue;
                                }

                                $reason_slug_raw  = isset( $reason['slug'] ) ? $reason['slug'] : '';
                                $reason_label_raw = isset( $reason['label'] ) ? $reason['label'] : '';

                                $clean_scope['reasons'][] = array(
                                    'slug'  => sanitize_key( is_string( $reason_slug_raw ) ? $reason_slug_raw : '' ),
                                    'label' => sanitize_text_field( is_string( $reason_label_raw ) ? $reason_label_raw : '' ),
                                );
                            }
                        }

                        if ( isset( $scope['fields'] ) && is_array( $scope['fields'] ) ) {
                            foreach ( $scope['fields'] as $field ) {
                                if ( ! is_array( $field ) ) {
                                    continue;
                                }

                                $field_slug_raw     = isset( $field['slug'] ) ? $field['slug'] : '';
                                $field_label_raw    = isset( $field['label'] ) ? $field['label'] : '';
                                $field_type_raw     = isset( $field['type'] ) ? $field['type'] : '';
                                $field_required_raw = isset( $field['required'] ) ? $field['required'] : false;

                                $clean_scope['fields'][] = array(
                                    'slug'     => sanitize_key( is_string( $field_slug_raw ) ? $field_slug_raw : '' ),
                                    'label'    => sanitize_text_field( is_string( $field_label_raw ) ? $field_label_raw : '' ),
                                    'type'     => sanitize_key( is_string( $field_type_raw ) ? $field_type_raw : '' ),
                                    'required' => ! empty( $field_required_raw ),
                                );
                            }
                        }

                        $clean_scopes[ $slug ] = $clean_scope;
                    }

                    update_option( self::OPTION_SCOPES, $clean_scopes );
                }
            }
        }

        // Save Resolutions
        if ( isset( $_POST['resolutions'] ) ) {
            // Read raw array safely, validate type, and sanitize each value before use.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $resolutions_input = wp_unslash( $_POST['resolutions'] );

            $resolutions = array();
            if ( is_array( $resolutions_input ) ) {
                foreach ( $resolutions_input as $resolution_key => $resolution_value ) {
                    $sanitized_key = sanitize_key( is_string( $resolution_key ) ? $resolution_key : '' );
                    $sanitized_val = sanitize_text_field( is_string( $resolution_value ) ? $resolution_value : '' );

                    $resolutions[ $sanitized_key ] = ( 'true' === $sanitized_val || '1' === $sanitized_val );
                }
            }
            update_option( 'claim_desk_resolutions', $resolutions );
        }

        // Save Problems
        if ( isset( $_POST['problems'] ) ) {
            // Read raw JSON safely, decode it, and sanitize each item before use.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $problems_input = wp_unslash( $_POST['problems'] );
            if ( is_string( $problems_input ) ) {
                $problems_raw = $problems_input;
                $problems     = json_decode( $problems_raw, true );

                if ( is_array( $problems ) ) {
                    $clean_problems = array();

                    foreach ( $problems as $problem ) {
                        if ( ! is_array( $problem ) ) {
                            continue;
                        }

                        $problem_value_raw = isset( $problem['value'] ) ? $problem['value'] : '';
                        $problem_label_raw = isset( $problem['label'] ) ? $problem['label'] : '';

                        $clean_problems[] = array(
                            'value' => sanitize_title( is_string( $problem_value_raw ) ? $problem_value_raw : '' ),
                            'label' => sanitize_text_field( is_string( $problem_label_raw ) ? $problem_label_raw : '' ),
                        );
                    }

                    update_option( 'claim_desk_problems', $clean_problems );
                }
            }
        }

        // Save Conditions
        if ( isset( $_POST['conditions'] ) ) {
            // Read raw JSON safely, decode it, and sanitize each item before use.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $conditions_input = wp_unslash( $_POST['conditions'] );
            if ( is_string( $conditions_input ) ) {
                $conditions_raw = $conditions_input;
                $conditions     = json_decode( $conditions_raw, true );

                if ( is_array( $conditions ) ) {
                    $clean_conditions = array();

                    foreach ( $conditions as $condition ) {
                        if ( ! is_array( $condition ) ) {
                            continue;
                        }

                        $condition_value_raw = isset( $condition['value'] ) ? $condition['value'] : '';
                        $condition_label_raw = isset( $condition['label'] ) ? $condition['label'] : '';

                        $clean_conditions[] = array(
                            'value' => sanitize_title( is_string( $condition_value_raw ) ? $condition_value_raw : '' ),
                            'label' => sanitize_text_field( is_string( $condition_label_raw ) ? $condition_label_raw : '' ),
                        );
                    }

                    update_option( 'claim_desk_conditions', $clean_conditions );
                }
            }
        }

        // Save Claim Window
        if ( isset( $_POST['claim_window'] ) ) {
            // Read raw array safely, validate type, and sanitize each value before use.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $claim_window_input = wp_unslash( $_POST['claim_window'] );
            $claim_window     = array(
                'mode' => 'limited_days',
                'days' => 30,
            );

            if ( is_array( $claim_window_input ) ) {
                $mode_raw = isset( $claim_window_input['mode'] ) ? $claim_window_input['mode'] : 'limited_days';
                $days_raw = isset( $claim_window_input['days'] ) ? $claim_window_input['days'] : 30;

                $mode = sanitize_key( is_string( $mode_raw ) ? $mode_raw : 'limited_days' );
                if ( ! in_array( $mode, array( 'limited_days', 'no_limit', 'not_allowed' ), true ) ) {
                    $mode = 'limited_days';
                }

                $days = absint( $days_raw );
                if ( $days < 1 ) {
                    $days = 1;
                }

                $claim_window['mode'] = $mode;
                $claim_window['days'] = $days;
            }

            update_option( 'claim_desk_claim_window', $claim_window );
        }

        // Save Reminder Settings
        if ( isset( $_POST['reminder_settings'] ) ) {
            // Read raw array safely, validate type, and sanitize each value before use.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $reminder_input = wp_unslash( $_POST['reminder_settings'] );
            $reminder     = array(
                'enabled' => false,
                'delay' => '3',
                'custom_days' => 3,
            );

            if ( is_array( $reminder_input ) ) {
                $enabled_raw     = isset( $reminder_input['enabled'] ) ? $reminder_input['enabled'] : '';
                $delay_raw       = isset( $reminder_input['delay'] ) ? $reminder_input['delay'] : '3';
                $custom_days_raw = isset( $reminder_input['custom_days'] ) ? $reminder_input['custom_days'] : 3;

                $enabled = sanitize_text_field( is_string( $enabled_raw ) ? $enabled_raw : '' );
                $delay   = sanitize_key( is_string( $delay_raw ) ? $delay_raw : '3' );
                if ( ! in_array( $delay, array( '1', '2', '3', '7', 'custom' ), true ) ) {
                    $delay = '3';
                }

                $custom_days = absint( $custom_days_raw );
                if ( $custom_days < 1 ) {
                    $custom_days = 1;
                }

                $reminder = array(
                    'enabled' => ( 'true' === $enabled || '1' === $enabled ),
                    'delay' => $delay,
                    'custom_days' => $custom_days,
                );
            }

            update_option( 'claim_desk_reminder_settings', $reminder );
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
            'reminder_settings' => self::get_reminder_settings(),
        );

        wp_send_json_success( $data );
    }

}
