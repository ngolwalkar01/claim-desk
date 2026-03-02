<?php

/**
 * Handles database operations for the Claim Desk plugin.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/includes
 */

class Claim_Desk_DB_Handler {

    /**
     * Table names
     */
    private $table_claims;
    private $table_items;
    private $table_attachments;

    public function __construct() {
        global $wpdb;
        $this->table_claims = $wpdb->prefix . 'cd_claims';
        $this->table_items = $wpdb->prefix . 'cd_claim_items';
        $this->table_attachments = $wpdb->prefix . 'cd_claim_attachments';
    }

    /**
     * Create a new claim.
     * 
     * @param array $data Claim data (order_id, user_id, type_slug, etc.)
     * @return int|false New Claim ID or false on failure.
     */
    public function create_claim( $data ) {
        global $wpdb;

        $defaults = array(
            'status' => 'pending',
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' )
        );

        $args = wp_parse_args( $data, $defaults );

        $format = array( '%d', '%d', '%s', '%s', '%s', '%s' );
        
        // We need 100% strict column mapping for $wpdb->insert
        $insert_data = array(
            'order_id'   => absint( $args['order_id'] ),
            'user_id'    => absint( $args['user_id'] ),
            'type_slug'  => sanitize_text_field( $args['type_slug'] ),
            'status'     => sanitize_key( $args['status'] ),
            'created_at' => $args['created_at'],
            'updated_at' => $args['updated_at']
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->insert( $this->table_claims, $insert_data, $format );

        if ( $result ) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Create a claim item.
     * 
     * @param array $data Item data.
     * @return int|false New Item ID or false.
     */
    public function create_claim_item( $data ) {
        global $wpdb;

        $insert_data = array(
            'claim_id'      => absint( $data['claim_id'] ),
            'order_item_id' => absint( $data['order_item_id'] ),
            'product_id'    => absint( $data['product_id'] ),
            'qty_total'     => absint( $data['qty_total'] ),
            'qty_claimed'   => absint( $data['qty_claimed'] ),
            'reason_slug'   => sanitize_key( $data['reason_slug'] ),
            'dynamic_data'  => isset( $data['dynamic_data'] ) ? $data['dynamic_data'] : null // JSON string
        );

        $format = array( '%d', '%d', '%d', '%d', '%d', '%s', '%s' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->insert( $this->table_items, $insert_data, $format );

        if ( $result ) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get all claimed items for a specific order.
     * 
     * @param int $order_id
     * @return array List of objects with order_item_id and qty_claimed
     */
    public function get_claimed_items_by_order( $order_id ) {
        global $wpdb;

        $order_id = absint( $order_id );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT i.order_item_id, i.qty_claimed, c.status
                FROM {$this->table_items} i
                JOIN {$this->table_claims} c ON i.claim_id = c.id
                WHERE c.order_id = %d 
                AND c.status != 'rejected'", $order_id ) );
        
        if ( empty( $results ) ) {
            // Check if ANY claims exist for this order
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $check_claims = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_claims} WHERE order_id = %d",
                    $order_id
                )
            );

            if ( ! empty( $check_claims ) ) {
                 // Check items for first claim
                 $first_claim_id = (int) $check_claims[0]->id;

                 // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                 $check_items = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$this->table_items} WHERE claim_id = %d",
                        $first_claim_id
                    )
                );
            }
        }

        return $results;
    }

    /**
     * Save file attachment metadata.
     * 
     * @param array $data Attachment data (claim_id, file_path, file_name, file_type, file_size)
     * @return int|false Attachment ID or false on failure.
     */
    public function save_attachment( $data ) {
        global $wpdb;

        $insert_data = array(
            'claim_id'  => absint( $data['claim_id'] ),
            'file_path' => sanitize_text_field( $data['file_path'] ),
            'file_name' => sanitize_text_field( $data['file_name'] ),
            'file_type' => sanitize_text_field( $data['file_type'] ),
            'file_size' => absint( $data['file_size'] ),
        );

        $format = array( '%d', '%s', '%s', '%s', '%d' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->insert( $this->table_attachments, $insert_data, $format );

        if ( $result ) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get all attachments for a claim.
     * 
     * @param int $claim_id Claim ID
     * @return array List of attachment objects
     */
    public function get_claim_attachments( $claim_id ) {
        global $wpdb;

        $claim_id = absint($claim_id);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_attachments} WHERE claim_id = %d ORDER BY uploaded_at ASC",
                $claim_id
            )
        );
    }

}
