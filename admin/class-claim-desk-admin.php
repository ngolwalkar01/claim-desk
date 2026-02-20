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
        if( isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id']) ) {
            // Verify nonce before processing GET data
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'view_claim' ) ) {
                wp_die( 'Security check failed' );
            }
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $table_claims = esc_sql($wpdb->prefix . 'cd_claims');
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $table_items = esc_sql($wpdb->prefix . 'cd_claim_items');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $claim = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_claims WHERE id = %d", $claim_id ) );
        
        if( ! $claim ) {
            echo '<div class="error"><p>Claim not found.</p></div>';
            return;
        }


        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_items WHERE claim_id = %d", $claim_id ) );
        $user = get_userdata( $claim->user_id );
        
        ?>
        <div class="cd-detail-view">
            <h3>
                <?php
                /* translators: %1$d = Claim ID, %2$d = Order ID. Numbered placeholders allow translators to reorder text based on language grammar. */
                printf(
                    esc_html__( 'Claim #%1$d - Order #%2$d', 'claim-desk' ),
                    absint( $claim->id ),
                    absint( $claim->order_id )
                ); 
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
                    
                    // Hidden data for JS
                    echo '<script type="application/json" id="cd-attachments-data">';
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
                    echo json_encode($attachments_data);
                    echo '</script>';
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

        <style>
            .cd-status-badge { padding: 5px 10px; border-radius: 4px; color: #fff; font-size: 12px; margin-left: 10px; }
            .cd-status-badge.pending { background: orange; }
            .cd-status-badge.approved { background: green; }
            .cd-status-badge.rejected { background: red; }

            /* Gallery Styles */
            .cd-gallery-section {
                margin: 20px 0;
            }

            .cd-gallery-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 12px;
                margin-top: 15px;
            }

            .cd-gallery-thumb {
                position: relative;
                overflow: hidden;
                border-radius: 6px;
                cursor: pointer;
                border: 2px solid #dcdcde;
                background: #f5f5f5;
                transition: all 0.3s ease;
            }

            .cd-gallery-thumb:hover {
                border-color: #0073aa;
                box-shadow: 0 2px 8px rgba(0, 115, 170, 0.2);
                transform: scale(1.05);
            }

            .cd-gallery-thumb img {
                width: 100%;
                height: 120px;
                object-fit: cover;
                display: block;
            }

            .cd-thumb-info {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(0, 0, 0, 0.7);
                color: #fff;
                padding: 6px;
                font-size: 11px;
                max-height: 50%;
                overflow: hidden;
            }

            .cd-filename {
                display: block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .cd-filesize {
                display: block;
                font-size: 10px;
                opacity: 0.8;
            }

            /* Lightbox Modal Styles */
            .cd-lightbox-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.95);
                z-index: 10000;
                align-items: center;
                justify-content: center;
                animation: cd-fade-in 0.3s ease;
            }

            .cd-lightbox-modal.active {
                display: flex;
            }

            @keyframes cd-fade-in {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            .cd-lightbox-container {
                width: 90%;
                max-width: 900px;
                max-height: 90vh;
                display: flex;
                flex-direction: column;
                background: #1e1e1e;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
            }

            .cd-lightbox-close {
                position: absolute;
                top: 10px;
                right: 10px;
                background: rgba(255, 255, 255, 0.1);
                color: #fff;
                border: none;
                font-size: 40px;
                cursor: pointer;
                width: 50px;
                height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                transition: background 0.2s;
                z-index: 10001;
            }

            .cd-lightbox-close:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .cd-lightbox-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px 20px;
                background: #2a2a2a;
                border-bottom: 1px solid #3a3a3a;
            }

            .cd-lightbox-info {
                display: flex;
                gap: 15px;
                align-items: center;
                color: #fff;
            }

            .cd-current-index {
                font-size: 14px;
                opacity: 0.9;
            }

            .cd-image-name {
                font-size: 14px;
                max-width: 300px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                opacity: 0.9;
            }

            .cd-lightbox-controls {
                display: flex;
                gap: 10px;
                align-items: center;
            }

            .cd-zoom-btn, .cd-reset-btn {
                background: #0073aa;
                color: #fff;
                border: none;
                width: 36px;
                height: 36px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.2s;
            }

            .cd-zoom-btn:hover, .cd-reset-btn:hover {
                background: #005a87;
            }

            .cd-zoom-btn:active, .cd-reset-btn:active {
                transform: scale(0.95);
            }

            .cd-zoom-level {
                color: #fff;
                font-size: 12px;
                min-width: 40px;
                text-align: center;
            }

            .cd-reset-btn {
                font-size: 12px;
                width: auto;
                padding: 0 8px;
            }

            .cd-lightbox-body {
                display: flex;
                align-items: center;
                justify-content: center;
                flex: 1;
                position: relative;
                overflow: hidden;
                background: #000;
            }

            .cd-lightbox-image-container {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: auto;
            }

            .cd-lightbox-image {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
                cursor: zoom-in;
                transition: transform 0.3s ease;
            }

            .cd-nav-btn {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                background: rgba(255, 255, 255, 0.2);
                color: #fff;
                border: none;
                width: 50px;
                height: 50px;
                font-size: 30px;
                cursor: pointer;
                border-radius: 4px;
                transition: background 0.2s;
                z-index: 10;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .cd-nav-btn:hover {
                background: rgba(255, 255, 255, 0.4);
            }

            .cd-nav-prev {
                left: 15px;
            }

            .cd-nav-next {
                right: 15px;
            }

            .cd-lightbox-footer {
                display: flex;
                gap: 20px;
                padding: 12px 20px;
                background: #2a2a2a;
                border-top: 1px solid #3a3a3a;
                font-size: 12px;
                color: #aaa;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .cd-gallery-grid {
                    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                }

                .cd-lightbox-container {
                    width: 95%;
                    max-height: 95vh;
                }

                .cd-nav-btn {
                    width: 40px;
                    height: 40px;
                    font-size: 24px;
                }

                .cd-lightbox-close {
                    width: 40px;
                    height: 40px;
                    font-size: 30px;
                }

                .cd-lightbox-header {
                    flex-direction: column;
                    gap: 10px;
                    align-items: flex-start;
                }

                .cd-lightbox-controls {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
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

        $claim_id = isset( $_POST['claim_id'] ) ? intval( wp_unslash( $_POST['claim_id'] ) ) : 0;
        $status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';

        if ( ! $claim_id || ! in_array( $status, array( 'approved', 'rejected' ) ) ) {
            return;
        }

        global $wpdb;
        $table_claims = $wpdb->prefix . 'cd_claims';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->update( 
            $table_claims, 
            array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ), 
            array( 'id' => $claim_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

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
