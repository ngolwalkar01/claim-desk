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

            <div class="card">
                <h3><?php esc_html_e( 'Claim Reminder Settings', 'claim-desk' ); ?></h3>
                <p><?php esc_html_e( 'Send reminder email to admin if no action is taken on a claim within the selected time.', 'claim-desk' ); ?></p>

                <p>
                    <label>
                        <input type="checkbox" id="cd-reminder-enabled" value="1">
                        <?php esc_html_e( 'Enable admin reminder emails', 'claim-desk' ); ?>
                    </label>
                </p>

                <p>
                    <label for="cd-reminder-delay"><strong><?php esc_html_e( 'Reminder delay', 'claim-desk' ); ?></strong></label><br>
                    <select id="cd-reminder-delay">
                        <option value="1"><?php esc_html_e( '1 day', 'claim-desk' ); ?></option>
                        <option value="2"><?php esc_html_e( '2 days', 'claim-desk' ); ?></option>
                        <option value="3"><?php esc_html_e( '3 days', 'claim-desk' ); ?></option>
                        <option value="7"><?php esc_html_e( '7 days (1 week)', 'claim-desk' ); ?></option>
                        <option value="custom"><?php esc_html_e( 'Custom', 'claim-desk' ); ?></option>
                    </select>
                </p>

                <p id="cd-reminder-custom-days-wrap" style="display:none;">
                    <label for="cd-reminder-custom-days"><strong><?php esc_html_e( 'Custom days', 'claim-desk' ); ?></strong></label><br>
                    <input type="number" id="cd-reminder-custom-days" min="1" step="1" value="3" class="small-text">
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

    </div>

    <!-- Templates -->
    <script type="text/template" id="tmpl-cd-row">
        <tr class="cd-item-row">
            <td><input type="text" class="regular-text cd-item-label" value="{{label}}"></td>
            <td><input type="text" class="regular-text cd-item-value" value="{{value}}"></td>
            <td><span class="dashicons dashicons-trash cd-remove-row" style="cursor:pointer; color:red;"></span></td>
        </tr>
    </script>

