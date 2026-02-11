<!-- Admin Config Page -->
<div class="wrap claim-desk-config-wrapper">
    
    <div class="cd-header">
        <h2><?php _e( 'Configuration', 'claim-desk' ); ?></h2>
        <button id="cd-save-config" class="button button-primary"><?php _e( 'Save Changes', 'claim-desk' ); ?></button>
        <span class="spinner"></span>
    </div>

    <h2 class="nav-tab-wrapper">
        <a href="#tab-general" class="nav-tab nav-tab-active"><?php _e('General Settings', 'claim-desk'); ?></a>
        <a href="#tab-problems" class="nav-tab"><?php _e('Problem Types', 'claim-desk'); ?></a>
        <a href="#tab-conditions" class="nav-tab"><?php _e('Product Conditions', 'claim-desk'); ?></a>
    </h2>

    <div id="cd-config-container">
        
        <!-- General Tab -->
        <div id="tab-general" class="cd-tab-content active">
            <div class="card">
                <h3><?php _e('Enabled Resolutions', 'claim-desk'); ?></h3>
                <p><?php _e('Select which resolution types are available to customers.', 'claim-desk'); ?></p>
                
                <p>
                    <label>
                        <input type="checkbox" name="cd_resolution[]" value="return" id="res-return"> 
                        <?php _e('Return & Refund', 'claim-desk'); ?>
                    </label>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="cd_resolution[]" value="exchange" id="res-exchange"> 
                        <?php _e('Exchange / Replacement', 'claim-desk'); ?>
                    </label>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="cd_resolution[]" value="coupon" id="res-coupon"> 
                        <?php _e('Store Credit / Coupon', 'claim-desk'); ?>
                    </label>
                </p>
            </div>
        </div>

        <!-- Problems Tab -->
        <div id="tab-problems" class="cd-tab-content" style="display:none;">
            <div class="card">
                <h3><?php _e('Problem Types', 'claim-desk'); ?></h3>
                <p><?php _e('Define the reasons a customer can select for a claim.', 'claim-desk'); ?></p>
                
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Label', 'claim-desk'); ?></th>
                            <th><?php _e('Value (Slug)', 'claim-desk'); ?></th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cd-problems-list">
                        <!-- JS Rendered -->
                    </tbody>
                </table>
                <p><button class="button" id="cd-add-problem">+ <?php _e('Add Problem Type', 'claim-desk'); ?></button></p>
            </div>
        </div>

        <!-- Conditions Tab -->
        <div id="tab-conditions" class="cd-tab-content" style="display:none;">
            <div class="card">
                <h3><?php _e('Product Conditions', 'claim-desk'); ?></h3>
                <p><?php _e('Define the condition options available to users.', 'claim-desk'); ?></p>
                
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Label', 'claim-desk'); ?></th>
                            <th><?php _e('Value (Slug)', 'claim-desk'); ?></th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cd-conditions-list">
                        <!-- JS Rendered -->
                    </tbody>
                </table>
                <p><button class="button" id="cd-add-condition">+ <?php _e('Add Condition', 'claim-desk'); ?></button></p>
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
</div>
