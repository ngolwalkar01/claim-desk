<!-- Admin Config Page -->
<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wrap claim-desk-config-wrapper">
    
    <div class="cd-header">
        <h2><?php esc_html_e( 'Configuration', 'claim-desk' ); ?></h2>
        <button id="cd-save-config" class="button button-primary"><?php esc_html_e( 'Save Changes', 'claim-desk' ); ?></button>
        <span class="spinner"></span>
    </div>

    <h2 class="nav-tab-wrapper">
        <a href="#tab-general" class="nav-tab nav-tab-active"><?php esc_html_e('General Settings', 'claim-desk'); ?></a>
        <a href="#tab-problems" class="nav-tab"><?php esc_html_e('Problem Types', 'claim-desk'); ?></a>
        <a href="#tab-conditions" class="nav-tab"><?php esc_html_e('Product Conditions', 'claim-desk'); ?></a>
        <a href="#tab-legacy" class="nav-tab"><?php esc_html_e('Advanced Scopes (Legacy)', 'claim-desk'); ?></a>
    </h2>

    <div id="cd-config-container">
        
        <!-- General Tab -->
        <div id="tab-general" class="cd-tab-content active">
            <!-- (Content maintained) -->
             <div class="card">
                <h3><?php esc_html_e('Enabled Resolutions', 'claim-desk'); ?></h3>
                <p><?php esc_html_e('Select which resolution types are available to customers.', 'claim-desk'); ?></p>
                
                <p>
                    <label>
                        <input type="checkbox" name="cd_resolution[]" value="return" id="res-return"> 
                        <?php esc_html_e('Return & Refund', 'claim-desk'); ?>
                    </label>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="cd_resolution[]" value="exchange" id="res-exchange"> 
                        <?php esc_html_e('Exchange / Replacement', 'claim-desk'); ?>
                    </label>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="cd_resolution[]" value="coupon" id="res-coupon"> 
                        <?php esc_html_e('Store Credit / Coupon', 'claim-desk'); ?>
                    </label>
                </p>
            </div>

            <div class="card">
                <h3><?php esc_html_e( 'Claim Window', 'claim-desk' ); ?></h3>
                <p><?php esc_html_e( 'Control whether customers can create claims and for how long after order completion.', 'claim-desk' ); ?></p>

                <p>
                    <label for="cd-claim-window-mode"><strong><?php esc_html_e( 'Claim window mode', 'claim-desk' ); ?></strong></label><br>
                    <select id="cd-claim-window-mode">
                        <option value="limited_days"><?php esc_html_e( 'Allow claims for limited days', 'claim-desk' ); ?></option>
                        <option value="no_limit"><?php esc_html_e( 'Allow claims with no time limit', 'claim-desk' ); ?></option>
                        <option value="not_allowed"><?php esc_html_e( 'Claims not allowed', 'claim-desk' ); ?></option>
                    </select>
                </p>

                <p id="cd-claim-window-days-wrap">
                    <label for="cd-claim-window-days"><strong><?php esc_html_e( 'Claim window (days)', 'claim-desk' ); ?></strong></label><br>
                    <input type="number" id="cd-claim-window-days" min="1" step="1" value="30" class="small-text">
                </p>
            </div>
        </div>

        <!-- Problems Tab -->
        <div id="tab-problems" class="cd-tab-content" style="display:none;">
             <!-- (Content maintained) -->
            <div class="card">
                <h3><?php esc_html_e('Problem Types', 'claim-desk'); ?></h3>
                <p><?php esc_html_e('Define the reasons a customer can select for a claim.', 'claim-desk'); ?></p>
                
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Label', 'claim-desk'); ?></th>
                            <th><?php esc_html_e('Value (Slug)', 'claim-desk'); ?></th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cd-problems-list">
                        <!-- JS Rendered -->
                    </tbody>
                </table>
                <p><button class="button" id="cd-add-problem">+ <?php esc_html_e('Add Problem Type', 'claim-desk'); ?></button></p>
            </div>
        </div>

        <!-- Conditions Tab -->
        <div id="tab-conditions" class="cd-tab-content" style="display:none;">
            <!-- (Content maintained) -->
            <div class="card">
                <h3><?php esc_html_e('Product Conditions', 'claim-desk'); ?></h3>
                <p><?php esc_html_e('Define the condition options available to users.', 'claim-desk'); ?></p>
                
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Label', 'claim-desk'); ?></th>
                            <th><?php esc_html_e('Value (Slug)', 'claim-desk'); ?></th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cd-conditions-list">
                        <!-- JS Rendered -->
                    </tbody>
                </table>
                <p><button class="button" id="cd-add-condition">+ <?php esc_html_e('Add Condition', 'claim-desk'); ?></button></p>
            </div>
        </div>

        <!-- Legacy Tab -->
        <div id="tab-legacy" class="cd-tab-content" style="display:none;">
            <div class="card">
                 <h3><?php esc_html_e('Advanced Scopes (JSON Config)', 'claim-desk'); ?></h3>
                 <p class="description"><?php esc_html_e('This is the legacy configuration method. It allows complex nested rules but is harder to manage.', 'claim-desk'); ?></p>
                 <div id="cd-legacy-scopes-container">
                     <!-- Scopes Rendered Here -->
                 </div>
                 <button id="cd-add-scope" class="button button-secondary"><?php esc_html_e( '+ Add New Scope', 'claim-desk' ); ?></button>
            </div>
        </div>

    </div>

    <!-- Templates -->
    <script type="text/template" id="tmpl-cd-row">
        <tr class="cd-item-row">
            <td><input type="text" class="regular-text cd-item-label" value="{{label}}"></td>
            <td><input type="text" class="regular-text cd-item-value" value="{{value}}"></td>
            <td><span class="dashicons dashicons-trash cd-remove-row" style="cursor:pointer; color:red;"></span></td>
        </tr>
    </script>

    <!-- Legacy Template -->
    <script type="text/template" id="tmpl-cd-scope">
        <div class="cd-scope-card postbox" data-slug="{{slug}}" style="margin-top:10px;">
            <div class="postbox-header" style="padding:10px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                <h3 class="hndle" style="margin:0;">
                    <span class="dashicons dashicons-{{icon}}"></span> 
                    <span class="cd-scope-label-display">{{label}}</span>
                </h3>
                <div class="handle-actions">
                     <button type="button" class="cd-remove-scope dashicons dashicons-trash" style="color:red; cursor:pointer; border:none; background:none;"></button>
                </div>
            </div>
            <div class="inside" style="padding:10px;">
                <div class="cd-row">
                    <label>Label: <input type="text" class="cd-scope-label-input" value="{{label}}"></label>
                    <label>Slug: <input type="text" class="cd-scope-slug-input" value="{{slug}}" readonly style="background:#eee;"></label>
                    <label>Icon: <input type="text" class="cd-scope-icon-input" value="{{icon}}"></label>
                </div>
                
                <hr>

                <div class="cd-split-view" style="display:flex; gap:20px;">
                    <div class="cd-sub-section" style="flex:1;">
                        <h4>Reasons</h4>
                        <div class="cd-reasons-list"></div>
                        <button class="button cd-add-reason">+ Add Reason</button>
                    </div>
                    <div class="cd-sub-section" style="flex:1;">
                        <h4>Fields</h4>
                        <div class="cd-fields-list"></div>
                        <button class="button cd-add-field">+ Add Field</button>
                    </div>
                </div>
            </div>
        </div>
    </script>
