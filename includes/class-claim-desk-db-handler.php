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

        $format = array( '%d', '%d', '%s', '%s', '%s', '%s' ); // Adjust based on columns used
        
        // We need 100% strict column mapping for $wpdb->insert
        $insert_data = array(
            'order_id'   => absint( $args['order_id'] ),
            'user_id'    => absint( $args['user_id'] ),
            'type_slug'  => sanitize_text_field( $args['type_slug'] ),
            'status'     => sanitize_key( $args['status'] ),
            'created_at' => $args['created_at'],
            'updated_at' => $args['updated_at']
        );

        $result = $wpdb->insert( $this->table_claims, $insert_data );

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
            'claim_id'      => $data['claim_id'],
            'order_item_id' => $data['order_item_id'],
            'product_id'    => $data['product_id'],
            'qty_total'     => $data['qty_total'],
            'qty_claimed'   => $data['qty_claimed'],
            'reason_slug'   => $data['reason_slug'],
            'dynamic_data'  => isset($data['dynamic_data']) ? $data['dynamic_data'] : null // JSON string
        );

        $result = $wpdb->insert( $this->table_items, $insert_data );

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
        


        $sql = "SELECT i.order_item_id, i.qty_claimed, c.status
                FROM " . $this->table_items . " i
                JOIN " . $this->table_claims . " c ON i.claim_id = c.id
                WHERE c.order_id = %d 
                AND c.status != 'rejected'"; 
        
        $query = $wpdb->prepare( $sql, $order_id );

        $results = $wpdb->get_results( $query );
        
        if ( empty( $results ) ) {
            // Check if ANY claims exist for this order
            $query_claims = "SELECT * FROM " . $this->table_claims . " WHERE order_id = %d";
            $check_claims = $wpdb->get_results( $wpdb->prepare( $query_claims, $order_id ) );
            if ( ! empty( $check_claims ) ) {
                 // Check items for first claim
                 $first_claim_id = $check_claims[0]->id;
                 $query_items = "SELECT * FROM " . $this->table_items . " WHERE claim_id = %d";
                 $check_items = $wpdb->get_results( $wpdb->prepare( $query_items, $first_claim_id ) );
            } else {
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

        $result = $wpdb->insert( $this->table_attachments, $insert_data );

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

        $sql = "SELECT * FROM " . $this->table_attachments . " WHERE claim_id = %d ORDER BY uploaded_at ASC";
        $query = $wpdb->prepare( $sql, $claim_id );

        return $wpdb->get_results( $query );
    }

}
