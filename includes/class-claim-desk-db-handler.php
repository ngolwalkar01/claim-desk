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

    public function __construct() {
        global $wpdb;
        $this->table_claims = $wpdb->prefix . 'cd_claims';
        $this->table_items = $wpdb->prefix . 'cd_claim_items';
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
        
        // Log inputs
        error_log( "Claim Desk Debug: get_claimed_items_by_order Order ID: " . $order_id );

        $sql = "SELECT i.order_item_id, i.qty_claimed, c.status
                FROM {$this->table_items} i
                JOIN {$this->table_claims} c ON i.claim_id = c.id
                WHERE c.order_id = %d 
                AND c.status != 'rejected'"; 
        
        $query = $wpdb->prepare( $sql, $order_id );
        error_log( "Claim Desk Debug: SQL Query: " . $query );

        $results = $wpdb->get_results( $query );
        
        if ( empty( $results ) ) {
            error_log( "Claim Desk Debug: No claimed items found for Order ID " . $order_id );
            // Check if ANY claims exist for this order
            $check_claims = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_claims} WHERE order_id = %d", $order_id ) );
            if ( ! empty( $check_claims ) ) {
                 error_log( "Claim Desk Debug: Claims found in cd_claims table: " . print_r( $check_claims, true ) );
                 // Check items for first claim
                 $first_claim_id = $check_claims[0]->id;
                 $check_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_items} WHERE claim_id = %d", $first_claim_id ) );
                 error_log( "Claim Desk Debug: Items for Claim #$first_claim_id: " . print_r( $check_items, true ) );
            } else {
                 error_log( "Claim Desk Debug: No claims found in cd_claims table either." );
            }
        } else {
            error_log( "Claim Desk Debug: Claimed items found: " . print_r( $results, true ) );
        }

        return $results;
    }

}
