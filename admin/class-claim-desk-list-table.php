<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Claim_Desk_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Claim', 'claim-desk' ),
            'plural'   => __( 'Claims', 'claim-desk' ),
            'ajax'     => false
        ) );

        $this->_column_headers = array( 
            $this->get_columns(),
            array(), // hidden
            $this->get_sortable_columns()
        );
    }

    public function get_columns() {
        return array(
            'cb'          => '<input type="checkbox" />',
            'id'          => __( 'Claim ID', 'claim-desk' ),
            'order'       => __( 'Order', 'claim-desk' ),
            'type_slug'   => __( 'Type', 'claim-desk' ),
            'status'      => __( 'Status', 'claim-desk' ),
            'created_at'  => __( 'Date', 'claim-desk' ),
            'action'      => __( 'Action', 'claim-desk' )
        );
    }

    public function get_sortable_columns() {
        return array(
            'id' => array( 'id', false ),
            'created_at' => array( 'created_at', false ),
            'status' => array( 'status', false )
        );
    }

    public function prepare_items() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cd_claims';
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        // Sorting
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';

        $allowed_sort_columns = array( 'id', 'created_at', 'status' );
        if ( ! in_array( $orderby, $allowed_sort_columns ) ) {
            $orderby = 'created_at';
        }
        
        // Ensure $order is uppercase and valid (ASC/DESC)
        $order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

        // Query
        // Count total items
        $table_name  = esc_sql( $table_name );
        $cache_key_total = 'cd_claims_total_count';
        $total_items = wp_cache_get( $cache_key_total, 'claim-desk' );

        if ( false === $total_items ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $total_items = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            wp_cache_set( $cache_key_total, $total_items, 'claim-desk', 300 );
        }
        
        // Build SQL query with validated ORDER BY
        $version = wp_cache_get( 'cd_claims_version', 'claim-desk' );
        if ( false === $version ) {
             $version = time();
             wp_cache_set( 'cd_claims_version', $version, 'claim-desk', 3600 );
        }

        $cache_key_items = 'cd_claims_items_' . $version . '_' . md5( $orderby . $order . $per_page . $offset );
        $items = wp_cache_get( $cache_key_items, 'claim-desk' );

        if ( false === $items ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name ORDER BY %s %s LIMIT %d OFFSET %d", $orderby, $order, $per_page, $offset ) );
            wp_cache_set( $cache_key_items, $items, 'claim-desk', 300 );
        }

        $this->items = $items;



        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ) );
    }

    protected function column_default( $item, $column_name ) {
        return isset( $item->$column_name ) ? $item->$column_name : '';
    }

    public function column_id( $item ) {
        return '#' . esc_html($item->id);
    }

    public function column_order( $item ) {
        $order = wc_get_order( $item->order_id );
        if( $order ) {
            return '<a href="' . esc_url($order->get_edit_order_url()) . '">#' . esc_html($item->order_id) . ' ' . esc_html($order->get_billing_first_name()) . '</a>';
        }
        return '#' . esc_html($item->order_id);
    }

    public function column_type_slug( $item ) {
        // Map slug to Label if possible (requires config manager)
        return esc_html(ucfirst( $item->type_slug ));
    }

    public function column_status( $item ) {
        $status = $item->status;
        $color = '#999';
        if($status == 'pending') $color = 'orange';
        if($status == 'approved') $color = 'green';
        if($status == 'rejected') $color = 'red';

        return sprintf( '<span style="color:%s; font-weight:bold;">%s</span>', esc_attr($color), esc_html(ucfirst( $status )) );
    }

    public function column_created_at( $item ) {
        return esc_html($item->created_at);
    }

    public function column_action( $item ) {
        $msg = __('View Details', 'claim-desk');
        // Link to detail view (page=claim-desk&tab=claims&action=view&id=123)
        $url = add_query_arg( array(
            'page' => 'claim-desk',
            'tab' => 'claims',
            'action' => 'view',
            'id' => $item->id,
            '_wpnonce' => wp_create_nonce( 'view_claim' )
        ), admin_url( 'admin.php' ) );

        return sprintf( '<a href="%s" class="button">%s</a>', esc_url($url), esc_html($msg) );
    }

    public function get_bulk_actions() {
        return array(
            'delete' => __( 'Delete', 'claim-desk' ),
            'reject' => __( 'Reject', 'claim-desk' ),
            'approve' => __( 'Approve', 'claim-desk' )
        );
    }

    public function process_bulk_action() {
        $action = $this->current_action();
        
        if ( ! $action ) return;

        // Security check
        check_admin_referer( 'bulk-claims' );

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $claim_ids = isset( $_GET['claim'] ) ? array_map( 'intval', $_GET['claim'] ) : array();

        if ( empty( $claim_ids ) ) return;

        global $wpdb;
        $table_claims = $wpdb->prefix . 'cd_claims';
        $table_items = $wpdb->prefix . 'cd_claim_items';

        if ( 'delete' === $action ) {
            foreach ( $claim_ids as $id ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->delete( $table_items, array( 'claim_id' => $id ) );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->delete( $table_claims, array( 'id' => $id ) );
            }
            echo '<div class="updated"><p>' . esc_html__( 'Claims deleted.', 'claim-desk' ) . '</p></div>';
            wp_cache_delete( 'cd_claims_total_count', 'claim-desk' );
            wp_cache_set( 'cd_claims_version', time(), 'claim-desk' );
        }

        if ( 'reject' === $action ) {
            foreach ( $claim_ids as $id ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->update( $table_claims, array( 'status' => 'rejected' ), array( 'id' => $id ) );
            }
            echo '<div class="updated"><p>' . esc_html__( 'Claims rejected.', 'claim-desk' ) . '</p></div>';
            wp_cache_delete( 'cd_claims_total_count', 'claim-desk' );
            wp_cache_set( 'cd_claims_version', time(), 'claim-desk' );
        }

        if ( 'approve' === $action ) {
            foreach ( $claim_ids as $id ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->update( $table_claims, array( 'status' => 'approved' ), array( 'id' => $id ) );
            }
            echo '<div class="updated"><p>' . esc_html__( 'Claims approved.', 'claim-desk' ) . '</p></div>';
            wp_cache_delete( 'cd_claims_total_count', 'claim-desk' );
            wp_cache_set( 'cd_claims_version', time(), 'claim-desk' );
        }
    }

    // Fix for checkbox column to ensure it uses the correct column name
    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="claim[]" value="%s" />', $item->id
        );
    }

    // ... (rest of methods)
}
