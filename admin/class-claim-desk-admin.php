<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Claim_Desk
 * @subpackage Claim_Desk/admin
 */

class Claim_Desk_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version           The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/claim-desk-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/claim-desk-admin.js', array( 'jquery' ), $this->version, false );

        // Localize script for AJAX
        wp_localize_script( $this->plugin_name, 'claim_desk_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'claim_desk_admin_nonce' )
        ));

	}

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     * 
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {

        add_submenu_page(
            'woocommerce',
            __( 'Claim Desk', 'claim-desk' ),
            __( 'Claims', 'claim-desk' ),
            'manage_options',
            'claim-desk',
            array( $this, 'display_plugin_setup' )
        );

    }

    /**
     * Render the main setup page with Tabs.
     */
    public function display_plugin_setup() {
        $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'claims';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Claim Desk', 'claim-desk' ); ?></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=claim-desk&tab=claims" class="nav-tab <?php echo $active_tab == 'claims' ? 'nav-tab-active' : ''; ?>"><?php _e( 'All Claims', 'claim-desk' ); ?></a>
                <a href="?page=claim-desk&tab=config" class="nav-tab <?php echo $active_tab == 'config' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Configuration', 'claim-desk' ); ?></a>
            </nav>

            <div class="claim-desk-content" style="margin-top: 20px;">
                <?php
                if ( $active_tab == 'config' ) {
                    require_once plugin_dir_path( __FILE__ ) . 'partials/claim-desk-admin-config.php';
                } else {
                    $this->display_claims_list();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Claims List Table.
     */
    private function display_claims_list() {
        if( isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id']) ) {
             $this->display_claim_detail( intval($_GET['id']) );
        } else {
            require_once plugin_dir_path( __FILE__ ) . 'class-claim-desk-list-table.php';
            $list_table = new Claim_Desk_List_Table();
            $list_table->process_bulk_action(); // Process actions before preparing items
            $list_table->prepare_items();
            
            echo '<form method="get">';
            echo '<input type="hidden" name="page" value="claim-desk" />';
            // Nonce for bulk actions (action = bulk-claims)
            wp_nonce_field( 'bulk-claims' );
            $list_table->display();
            echo '</form>';
        }
    }

    /**
     * Display Single Claim Detail.
     */
    private function display_claim_detail( $claim_id ) {
        global $wpdb;
        $table_claims = $wpdb->prefix . 'cd_claims';
        $table_items = $wpdb->prefix . 'cd_claim_items';

        $claim = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_claims WHERE id = %d", $claim_id ) );
        
        if( ! $claim ) {
            echo '<div class="error"><p>Claim not found.</p></div>';
            return;
        }

        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_items WHERE claim_id = %d", $claim_id ) );
        $user = get_userdata( $claim->user_id );
        
        ?>
        <div class="cd-detail-view">
            <h3>
                <?php printf( __('Claim #%d - Order #%d', 'claim-desk'), $claim->id, $claim->order_id ); ?>
                <span class="cd-status-badge <?php echo $claim->status; ?>"><?php echo ucfirst($claim->status); ?></span>
            </h3>
            
            <div class="card">
                <h4>Customer Info</h4>
                <p><strong>Name:</strong> <?php echo $user ? esc_html($user->display_name) : 'Unknown'; ?></p>
                <p><strong>Email:</strong> <?php echo $user ? esc_html($user->user_email) : 'Unknown'; ?></p>
                <p><strong>Date:</strong> <?php echo esc_html($claim->created_at); ?></p>
            </div>

            <div class="card" style="margin-top:20px;">
                <h4>Claimed Items</h4>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Product</th>
                            <th>Qty Claimed</th>
                            <th>Reason</th>
                            <th>Details (JSON)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): 
                            $product = wc_get_product($item->product_id);
                        ?>
                        <tr>
                            <td>#<?php echo esc_html($item->order_item_id); ?></td>
                            <td>
                                <?php echo $product ? esc_html($product->get_name()) : 'Unknown Product'; ?>
                            </td>
                            <td><?php echo esc_html($item->qty_claimed); ?> / <?php echo esc_html($item->qty_total); ?></td>
                            <td><?php echo esc_html($item->reason_slug); ?></td>
                            <td>
<<<<<<< HEAD
                                <pre><?php echo esc_html(print_r(json_decode($item->dynamic_data, true), true)); ?></pre>
=======
                                <?php 
                                $data = json_decode($item->dynamic_data, true);
                                if ( ! empty($data) && is_array($data) ) {
                                    echo '<ul class="cd-data-list" style="margin:0; padding-left:15px;">';
                                    foreach ($data as $key => $value) {
                                        $label = ucwords(str_replace(['_', '-'], ' ', $key));
                                        echo '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</li>';
                                    }
                                    echo '</ul>';
                                } else {
                                    echo '<span class="description">No additional details</span>';
                                }
                                ?>
>>>>>>> 15dc740136b5268baba5e824f65eb8ac105d49da
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card" style="margin-top:20px;">
                <h4>Actions</h4>
                <form method="post" action="">
                    <?php wp_nonce_field( 'claim_desk_action', 'claim_desk_nonce' ); ?>
                    <input type="hidden" name="action" value="claim_desk_update_status">
                    <input type="hidden" name="claim_id" value="<?php echo esc_attr($claim_id); ?>">
                    
                    <?php if ( $claim->status !== 'approved' ): ?>
                        <button type="submit" name="status" value="approved" class="button button-primary" style="background:green; border-color:darkgreen;">Approve Claim</button>
                    <?php endif; ?>

                    <?php if ( $claim->status !== 'rejected' ): ?>
                        <button type="submit" name="status" value="rejected" class="button button-secondary" style="color:red; border-color:red;">Reject Claim</button>
                    <?php endif; ?>
                    
                    <a href="?page=claim-desk&tab=claims" class="button" style="margin-left:10px;">Back to List</a>
                </form>
            </div>

        </div>
        <style>
            .cd-status-badge { padding: 5px 10px; border-radius: 4px; color: #fff; font-size: 12px; margin-left: 10px; }
            .cd-status-badge.pending { background: orange; }
            .cd-status-badge.approved { background: green; }
            .cd-status-badge.rejected { background: red; }
        </style>
        <?php
    }

    /**
     * Process Status Update (Approve/Reject).
     * Hooked to admin_init.
     */
    public function process_status_update() {
        if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'claim_desk_update_status' ) {
            return;
        }

        if ( ! isset( $_POST['claim_desk_nonce'] ) || ! wp_verify_nonce( $_POST['claim_desk_nonce'], 'claim_desk_action' ) ) {
            wp_die( 'Security check failed' );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission denied' );
        }

        $claim_id = isset( $_POST['claim_id'] ) ? intval( $_POST['claim_id'] ) : 0;
        $status   = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';

        if ( ! $claim_id || ! in_array( $status, array( 'approved', 'rejected' ) ) ) {
            return;
        }

        global $wpdb;
        $table_claims = $wpdb->prefix . 'cd_claims';
        
        $wpdb->update( 
            $table_claims, 
            array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ), 
            array( 'id' => $claim_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        // Redirect to avoid resubmission
        $redirect_url = add_query_arg( array(
            'page'   => 'claim-desk',
            'tab'    => 'claims',
            'action' => 'view',
            'id'     => $claim_id,
            'msg'    => 'updated'
        ), admin_url( 'admin.php' ) );

        wp_redirect( $redirect_url );
        exit;
    }

}
