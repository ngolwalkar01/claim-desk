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
        $defaults = array(
            'quality' => array(
                'slug'    => 'quality',
                'label'   => __( 'Product Quality', 'claim-desk' ),
                'icon'    => 'box', // dashicon suffix
                'reasons' => array(
                    array( 'slug' => 'defective', 'label' => 'Defective' ),
                    array( 'slug' => 'damaged', 'label' => 'Damaged' )
                ),
                'fields'  => array(
                    array( 'slug' => 'description', 'type' => 'textarea', 'label' => 'Description', 'required' => true )
                )
            ),
            'delivery' => array(
                'slug'    => 'delivery',
                'label'   => __( 'Delivery Issue', 'claim-desk' ),
                'icon'    => 'truck',
                'reasons' => array(
                    array( 'slug' => 'not_received', 'label' => 'Not Received' ),
                    array( 'slug' => 'late', 'label' => 'Arrived Late' )
                ),
                'fields'  => array()
            )
        );

        return get_option( self::OPTION_SCOPES, $defaults );
    }

    /**
     * AJAX Handler: Save Configuration.
     */
    public function ajax_save_config() {
        check_ajax_referer( 'claim_desk_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'claim-desk' ) );
        }

        $scopes = isset( $_POST['scopes'] ) ? json_decode( stripslashes( $_POST['scopes'] ), true ) : null;

        if ( ! is_array( $scopes ) ) {
            wp_send_json_error( __( 'Invalid data format.', 'claim-desk' ) );
        }

        // Sanitize data (Basic sanitization for structured array)
        $clean_scopes = array();
        foreach ( $scopes as $key => $scope ) {
            $slug = sanitize_key( $scope['slug'] );
            $clean_scopes[ $slug ] = array(
                'slug'    => $slug,
                'label'   => sanitize_text_field( $scope['label'] ),
                'icon'    => sanitize_html_class( $scope['icon'] ),
                'reasons' => array_map( function( $r ) {
                    return array(
                        'slug'  => sanitize_key( $r['slug'] ),
                        'label' => sanitize_text_field( $r['label'] )
                    );
                }, isset($scope['reasons']) ? $scope['reasons'] : [] ),
                'fields'  => array_map( function( $f ) {
                    return array(
                        'slug'     => sanitize_key( $f['slug'] ),
                        'type'     => sanitize_key( $f['type'] ),
                        'label'    => sanitize_text_field( $f['label'] ),
                        'required' => !empty($f['required'])
                    );
                }, isset($scope['fields']) ? $scope['fields'] : [] )
            );
        }

        update_option( self::OPTION_SCOPES, $clean_scopes );

        wp_send_json_success( __( 'Configuration saved.', 'claim-desk' ) );
    }

    /**
     * AJAX Handler: Get Configuration.
     */
    public function ajax_get_config() {
        check_ajax_referer( 'claim_desk_admin_nonce', 'nonce' );
        wp_send_json_success( self::get_scopes() );
    }

}
