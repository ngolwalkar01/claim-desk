<!-- Claim Desk Modal -->
<div class="cd-modal-overlay">
    <div class="cd-modal">
        <div class="cd-modal-header">
            <h3><?php _e( 'Report a Problem', 'claim-desk' ); ?></h3>
            <button class="cd-close-modal">&times;</button>
        </div>
        
        <div class="cd-modal-body">
            
            <!-- Step 1: Scope Selection -->
            <div id="cd-step-scope" class="cd-step-view">
                <p class="cd-step-instruction"><?php _e( 'What kind of problem are you experiencing?', 'claim-desk' ); ?></p>
                <div class="cd-scope-selection" id="cd-scope-list">
                    <!-- Populated via JS -->
                </div>
            </div>

            <!-- Step 2: Item Selection -->
            <div id="cd-step-items" class="cd-step-view cd-hidden">
                <p class="cd-step-instruction"><?php _e( 'Which items are affected?', 'claim-desk' ); ?></p>
                <div class="cd-items-list" id="cd-items-container">
                    <p><?php _e( 'Loading items...', 'claim-desk' ); ?></p>
                </div>
            </div>

            <!-- Step 3: Details -->
            <div id="cd-step-details" class="cd-step-view cd-hidden">
                <p class="cd-step-instruction"><?php _e( 'Please provide more details.', 'claim-desk' ); ?></p>
                
                <form id="cd-details-form">
                    <!-- Reasons Section -->
                    <div class="cd-form-section">
                        <h4><?php _e( 'Reason', 'claim-desk' ); ?> <span class="cd-req">*</span></h4>
                        <div id="cd-reasons-container" class="cd-reasons-grid"></div>
                    </div>

                    <!-- Dynamic Fields Section -->
                    <div id="cd-fields-container"></div>
                </form>
            </div>

        </div>

        <div class="cd-modal-footer">
            <button class="button cd-modal-back cd-hidden"><?php _e( 'Back', 'claim-desk' ); ?></button>
            <button class="button button-primary cd-modal-next cd-hidden"><?php _e( 'Next', 'claim-desk' ); ?></button>
            <button class="button button-primary cd-modal-submit cd-hidden"><?php _e( 'Submit Claim', 'claim-desk' ); ?></button>
        </div>
    </div>
    
    <!-- Templates -->
    <script type="text/template" id="tmpl-cd-item-row">
        <div class="cd-item-select-row" data-id="{{id}}">
            <label class="cd-item-checkbox-wrapper">
                <input type="checkbox" class="cd-item-checkbox">
                <img src="{{image}}" class="cd-item-thumb" alt="">
                <div class="cd-item-meta">
                    <span class="cd-item-name">{{name}}</span>
                    <span class="cd-item-price">{{price}}</span>
                </div>
            </label>
            <div class="cd-item-qty-wrapper cd-hidden">
                <label>Qty:</label>
                <input type="number" class="cd-item-qty-input" min="1" max="{{max_qty}}" value="1">
                <span class="cd-max-qty">/ {{max_qty}}</span>
            </div>
        </div>
    </script>

    <script type="text/template" id="tmpl-cd-field-text">
        <div class="cd-form-group">
            <label>{{label}} {{required_mark}}</label>
            <input type="{{type}}" name="{{slug}}" class="cd-form-input" {{required}}>
        </div>
    </script>
    
    <script type="text/template" id="tmpl-cd-field-textarea">
        <div class="cd-form-group">
            <label>{{label}} {{required_mark}}</label>
            <textarea name="{{slug}}" class="cd-form-input" rows="4" {{required}}></textarea>
        </div>
    </script>

</div>
