<?php

/**
 * Manages the configuration (Scopes, Reasons, Fields) for the plugin.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes
 */

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
            array( 'value' => 'damaged', 'label' => __( 'Product Damaged', 'claim-desk' ) ),
            array( 'value' => 'defective', 'label' => __( 'Product Defective', 'claim-desk' ) ),
            array( 'value' => 'wrong-item', 'label' => __( 'Wrong Item Received', 'claim-desk' ) ),
            array( 'value' => 'wrong-size', 'label' => __( 'Wrong Size/Color', 'claim-desk' ) ),
            array( 'value' => 'not-as-described', 'label' => __( 'Not As Described', 'claim-desk' ) ),
            array( 'value' => 'quality-issue', 'label' => __( 'Quality Issue', 'claim-desk' ) ),
            array( 'value' => 'other', 'label' => __( 'Other', 'claim-desk' ) )
        );
        return get_option( 'claim_desk_problems', $defaults );
    }

    /**
     * Get configured product conditions.
     */
    public static function get_conditions() {
        $defaults = array(
            array( 'value' => 'unopened', 'label' => __( 'Unopened', 'claim-desk' ) ),
            array( 'value' => 'opened', 'label' => __( 'Opened', 'claim-desk' ) ),
            array( 'value' => 'damaged', 'label' => __( 'Damaged', 'claim-desk' ) )
        );
        return get_option( 'claim_desk_conditions', $defaults );
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
            $scopes = json_decode( stripslashes( $_POST['scopes'] ), true );
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
            $resolutions = array_map( function($val) { return $val === 'true' || $val === '1'; }, $_POST['resolutions'] );
            update_option( 'claim_desk_resolutions', $resolutions );
        }

        // Save Problems
        if ( isset( $_POST['problems'] ) ) {
            $problems = json_decode( stripslashes( $_POST['problems'] ), true );
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
            $conditions = json_decode( stripslashes( $_POST['conditions'] ), true );
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
            'conditions'  => self::get_conditions()
        );

        wp_send_json_success( $data );
    }

}
