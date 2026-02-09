<!-- Admin Config Page -->
<div class="wrap claim-desk-config-wrapper">
    
    <div class="cd-header">
        <h2><?php _e( 'Claim Scopes & Fields', 'claim-desk' ); ?></h2>
        <button id="cd-save-config" class="button button-primary"><?php _e( 'Save Configuration', 'claim-desk' ); ?></button>
        <span class="spinner"></span>
    </div>

    <div id="cd-config-container">
        <!-- Scopes will be rendered here -->
    </div>

    <button id="cd-add-scope" class="button button-secondary"><?php _e( '+ Add New Scope', 'claim-desk' ); ?></button>

    <!-- Templates (Hidden) -->
    <script type="text/template" id="tmpl-cd-scope">
        <div class="cd-scope-card postbox" data-slug="{{slug}}">
            <div class="postbox-header">
                <h2 class="hndle ui-sortable-handle">
                    <span class="dashicons dashicons-{{icon}}"></span> 
                    <span class="cd-scope-label-display">{{label}}</span>
                </h2>
                <div class="handle-actions">
                    <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: {{label}}</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                    <button type="button" class="cd-remove-scope dashicons dashicons-trash"></button>
                </div>
            </div>
            <div class="inside">
                <div class="cd-row">
                    <div class="cd-col">
                        <label>Scope Label</label>
                        <input type="text" class="regular-text cd-scope-label-input" value="{{label}}">
                    </div>
                    <div class="cd-col">
                        <label>Scope Slug (Unique ID)</label>
                        <input type="text" class="regular-text cd-scope-slug-input" value="{{slug}}" readonly>
                    </div>
                </div>
                <div class="cd-row">
                    <div class="cd-col">
                        <label>Icon (Dashicon)</label>
                        <input type="text" class="regular-text cd-scope-icon-input" value="{{icon}}">
                        <small>e.g. 'truck', 'box', 'shield'</small>
                    </div>
                </div>
                
                <hr>

                <div class="cd-split-view">
                    <!-- Reasons Column -->
                    <div class="cd-sub-section">
                        <h3>Reasons</h3>
                        <p class="description">Options for "What is the problem?"</p>
                        <div class="cd-reasons-list">
                            <!-- Reasons Rendered Here -->
                        </div>
                        <button class="button cd-add-reason">+ Add Reason</button>
                    </div>

                    <!-- Fields Column -->
                    <div class="cd-sub-section">
                        <h3>Dynamic Fields</h3>
                        <p class="description">Extra inputs (e.g. Batch No, PO)</p>
                        <div class="cd-fields-list">
                            <!-- Fields Rendered Here -->
                        </div>
                        <button class="button cd-add-field">+ Add Field</button>
                    </div>
                </div>
            </div>
        </div>
    </script>
</div>
