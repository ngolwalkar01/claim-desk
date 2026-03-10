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
	public function enqueue_styles( $hook_suffix ) {
		if ( ! $this->should_enqueue_admin_assets( $hook_suffix ) ) {
			return;
		}

		$style_path = plugin_dir_path( __FILE__ ) . 'css/claim-desk-admin.css';
		$style_ver  = file_exists( $style_path ) ? (string) filemtime( $style_path ) : $this->version;
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/claim-desk-admin.css', array(), $style_ver, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( ! $this->should_enqueue_admin_assets( $hook_suffix ) ) {
			return;
		}

		$script_path = plugin_dir_path( __FILE__ ) . 'js/claim-desk-admin.js';
		$script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : $this->version;
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/claim-desk-admin.js', array( 'jquery' ), $script_ver, true );

        // Localize script for AJAX
        wp_localize_script( $this->plugin_name, 'claim_desk_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'claim_desk_admin_nonce' )
        ));

	}

	private function should_enqueue_admin_assets( $hook_suffix ) {
		return 'woocommerce_page_claim-desk' === $hook_suffix;
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
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'claims';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Claim Desk', 'claim-desk' ); ?></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=claim-desk&tab=claims" class="nav-tab <?php echo $active_tab == 'claims' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'All Claims', 'claim-desk' ); ?></a>
                <a href="?page=claim-desk&tab=config" class="nav-tab <?php echo $active_tab == 'config' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Configuration', 'claim-desk' ); ?></a>
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
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $view_action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
        $view_id     = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;

        if ( 'view' === $view_action && $view_id ) {
            // Verify nonce before processing GET data
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'view_claim' ) ) {
                wp_die( 'Security check failed' );
            }
            $this->display_claim_detail( $view_id );
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
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $table_claims = esc_sql($wpdb->prefix . 'cd_claims');
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $table_items = esc_sql($wpdb->prefix . 'cd_claim_items');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $claim = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_claims} WHERE id = %d", $claim_id ) );
        
        if( ! $claim ) {
            echo '<div class="error"><p>Claim not found.</p></div>';
            return;
        }


        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_items} WHERE claim_id = %d", $claim_id ) );
        $user = get_userdata( $claim->user_id );
        
        ?>
        <div class="cd-detail-view">
            <h3>
                <?php
                $claim_title = sprintf(
                    /* translators: %1$d: Claim ID, %2$d: Order ID. */
                    esc_html__( 'Claim #%1$d - Order #%2$d', 'claim-desk' ),
                    absint( $claim->id ),
                    absint( $claim->order_id )
                );
                echo esc_html( $claim_title );
                ?>
                <span class="cd-status-badge <?php echo esc_attr($claim->status); ?>"><?php echo esc_html(ucfirst($claim->status)); ?></span>
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
                            <th>Product SKU</th>
                            <th>Product ID</th>
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
                            <td>
                                <?php echo $product ? esc_html( $product->get_sku() ? $product->get_sku() : '-' ) : '-'; ?>
                            </td>
                            <td>
                                <?php echo esc_html( absint( $item->product_id ) ); ?>
                            </td>
                            <td><?php echo esc_html($item->qty_claimed); ?> / <?php echo esc_html($item->qty_total); ?></td>
                            <td><?php echo esc_html($item->reason_slug); ?></td>
                            <td>
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
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card" style="margin-top:20px;">
                <h4>Uploaded Images</h4>
                <?php
                $db = new Claim_Desk_DB_Handler();
                $attachments = $db->get_claim_attachments($claim_id);
                
                if (!empty($attachments)) {
                    echo '<div class="cd-gallery-section">';
                    echo '<div class="cd-gallery-grid">';
                    
                    foreach ($attachments as $idx => $attachment) {
                        $file_url = wp_upload_dir()['baseurl'] . $attachment->file_path;
                        $file_size_kb = round($attachment->file_size / 1024, 2);
                        echo '<div class="cd-gallery-thumb" data-idx="' . esc_attr($idx) . '">';
                        echo '<img src="' . esc_url($file_url) . '" alt="' . esc_attr($attachment->file_name) . '" />';
                        echo '<div class="cd-thumb-info">';
                        echo '<span class="cd-filename">' . esc_html($attachment->file_name) . '</span>';
                        echo '<span class="cd-filesize">' . esc_html($file_size_kb) . ' KB</span>';
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                    echo '</div>';
                    
                    $attachments_data = array();
                    foreach ($attachments as $idx => $attachment) {
                        $file_url = wp_upload_dir()['baseurl'] . $attachment->file_path;
                        $attachments_data[] = array(
                            'idx' => $idx,
                            'url' => $file_url,
                            'name' => $attachment->file_name,
                            'size' => round($attachment->file_size / 1024, 2),
                            'date' => $attachment->uploaded_at
                        );
                    }
                    wp_add_inline_script(
                        $this->plugin_name,
                        'window.claimDeskAdminAttachments = ' . wp_json_encode( $attachments_data ) . ';',
                        'before'
                    );
                } else {
                    echo '<p style="color: #757575;"><em>No images uploaded</em></p>';
                }
                ?>
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

        <!-- Lightbox Modal -->
        <div id="cd-lightbox-modal" class="cd-lightbox-modal">
            <div class="cd-lightbox-container">
                <button class="cd-lightbox-close">&times;</button>
                
                <div class="cd-lightbox-header">
                    <div class="cd-lightbox-info">
                        <span class="cd-current-index"><span id="cd-current-idx">1</span> / <span id="cd-total-idx">1</span></span>
                        <span class="cd-image-name" id="cd-image-name">Image</span>
                    </div>
                    <div class="cd-lightbox-controls">
                        <button class="cd-zoom-btn cd-zoom-out" title="Zoom Out">−</button>
                        <span class="cd-zoom-level" id="cd-zoom-level">100%</span>
                        <button class="cd-zoom-btn cd-zoom-in" title="Zoom In">+</button>
                        <button class="cd-reset-btn cd-reset-zoom" title="Reset">Reset</button>
                    </div>
                </div>

                <div class="cd-lightbox-body">
                    <button class="cd-nav-btn cd-nav-prev" title="Previous">‹</button>
                    <div class="cd-lightbox-image-container">
                        <img id="cd-lightbox-image" class="cd-lightbox-image" src="" alt="Claim Image" />
                    </div>
                    <button class="cd-nav-btn cd-nav-next" title="Next">›</button>
                </div>

                <div class="cd-lightbox-footer">
                    <span id="cd-image-size"></span>
                    <span id="cd-image-date"></span>
                </div>
            </div>
        </div>

        </div>

        <?php
    }

    /**
     * Process Status Update (Approve/Reject).
     * Hooked to admin_init.
     */
    public function process_status_update() {
        $action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
        if ( $action !== 'claim_desk_update_status' ) {
            return;
        }

        $nonce = isset( $_POST['claim_desk_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['claim_desk_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'claim_desk_action' ) ) {
            wp_die( 'Security check failed' );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission denied' );
        }

        $claim_id = isset( $_POST['claim_id'] ) ? absint( wp_unslash( $_POST['claim_id'] ) ) : 0;
        $status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';

        if ( ! $claim_id || ! in_array( $status, array( 'approved', 'rejected' ) ) ) {
            return;
        }

        global $wpdb;
        $table_claims = $wpdb->prefix . 'cd_claims';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->update( 
            $table_claims, 
            array(
                'status' => $status,
                'updated_at' => current_time( 'mysql' ),
                'last_status_update_at' => current_time( 'mysql' ),
                'reminder_sent' => 0,
                'reminder_sent_at' => null,
            ),
            array( 'id' => $claim_id ),
            array( '%s', '%s', '%s', '%d', '%s' ),
            array( '%d' )
        );

        do_action( 'claim_desk_claim_status_updated', $claim_id, $status );

        // Redirect to avoid resubmission
        $redirect_url = add_query_arg( array(
            'page'     => 'claim-desk',
            'tab'      => 'claims',
            'action'   => 'view',
            'id'       => $claim_id,
            'msg'      => 'updated',
            '_wpnonce' => wp_create_nonce( 'view_claim' )
        ), admin_url( 'admin.php' ) );

        wp_safe_redirect( $redirect_url );
        exit;
    }

}
