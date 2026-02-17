<?php
/**
 * Frontend Wizard HTML
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$claim_desk_order_id = isset( $_GET['order_id'] ) ? intval( wp_unslash( $_GET['order_id'] ) ) : 0;
// Basic validation
if ( ! $claim_desk_order_id ) {
    echo '<div class="container"><p>Invalid Order ID.</p></div>';
    return;
}

// Get Order and Config Data (Passed to JS via localize_script, but we can also use PHP to render initial state if needed)
// For now, we keep the HTML structure and will hydrate it with JS.
?>
<div class="cd-wizard-container">
    <!-- Progress Stepper -->
    <div class="progress-stepper">
        <div class="stepper-wrapper">
            <div class="stepper-line">
                <div class="stepper-progress" id="stepperProgress"></div>
            </div>
            
            <div class="step active" data-step="1">
                <div class="step-indicator">1</div>
                <div class="step-title"><?php esc_html_e('Select Product', 'claim-desk'); ?></div>
            </div>
            
            <div class="step" data-step="2">
                <div class="step-indicator">2</div>
                <div class="step-title"><?php esc_html_e('Claim Details', 'claim-desk'); ?></div>
            </div>
            
            <div class="step" data-step="3">
                <div class="step-indicator">3</div>
                <div class="step-title"><?php esc_html_e('Review & Submit', 'claim-desk'); ?></div>
            </div>
        </div>
    </div>

    <!-- Main Form Card -->
    <div class="claim-card">
        <!-- Order Info Header -->
        <div class="card-header">
            <h1 class="card-title"><?php
            /* translators: %1$d = Order ID. Numbered placeholder allows translators to reorder text if needed. */
            printf( __('Claim for Order #%1$d', 'claim-desk'), $claim_desk_order_id );
            ?></h1>
            <p class="card-subtitle"><?php esc_html_e('Follow the steps below to submit your claim.', 'claim-desk'); ?></p>
        </div>

        <!-- STEP 1: Product Selection -->
        <div class="step-content active" id="step1">
            <div class="card-header">
                <h2 class="section-title"><?php esc_html_e('Select Products', 'claim-desk'); ?></h2>
            </div>

            <div class="product-grid" id="cd-product-grid">
                <!-- Products will be injected here via JS -->
                <p>Loading products...</p>
            </div>

            <div class="nav-buttons">
                <div></div>
                <button class="btn btn-primary" id="step1Next" disabled>
                    <?php esc_html_e('Next', 'claim-desk'); ?>
                    <span>→</span>
                </button>
            </div>
        </div>

        <!-- STEP 2: Claim Details -->
        <div class="step-content" id="step2">
            
            <!-- Claim Type Selection -->
            <div class="form-section">
                <h2 class="section-title"><?php esc_html_e('Select Claim Type', 'claim-desk'); ?></h2>
                <div class="claim-type-grid">
                    <div class="claim-type-card" data-claim-type="return">
                        <div class="claim-icon">↩</div>
                        <div class="claim-type-title"><?php esc_html_e('Return', 'claim-desk'); ?></div>
                        <div class="claim-type-desc"><?php esc_html_e('Get a full refund for your product', 'claim-desk'); ?></div>
                    </div>
                    
                    <div class="claim-type-card" data-claim-type="exchange">
                        <div class="claim-icon">⇄</div>
                        <div class="claim-type-title"><?php esc_html_e('Exchange', 'claim-desk'); ?></div>
                        <div class="claim-type-desc"><?php esc_html_e('Replace with same or different product', 'claim-desk'); ?></div>
                    </div>
                    
                    <div class="claim-type-card" data-claim-type="coupon">
                        <div class="claim-icon">🎟</div>
                        <div class="claim-type-title"><?php esc_html_e('Discount Coupon', 'claim-desk'); ?></div>
                        <div class="claim-type-desc"><?php esc_html_e('Get a discount for store credit', 'claim-desk'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Problem Details -->
            <div class="form-section">
                <h2 class="section-title"><?php esc_html_e('Problem Details', 'claim-desk'); ?></h2>
                
                <!-- Problem Type (Loaded from Config) -->
                <div class="form-group">
                    <label class="form-label required" for="problemType"><?php esc_html_e('Problem Type', 'claim-desk'); ?></label>
                    <select class="form-select" id="problemType">
                        <option value=""><?php esc_html_e('Select a problem type', 'claim-desk'); ?></option>
                        <!-- Options injected via JS -->
                    </select>
                    <div class="error-message"><?php esc_html_e('Please select a problem type', 'claim-desk'); ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="problemDescription"><?php esc_html_e('Describe the Issue', 'claim-desk'); ?></label>
                    <textarea class="form-textarea" id="problemDescription" placeholder="<?php esc_attr_e('Please provide details about the issue...', 'claim-desk'); ?>"></textarea>
                    <div class="error-message"><?php esc_html_e('Please describe the issue', 'claim-desk'); ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php esc_html_e('Upload Images', 'claim-desk'); ?></label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <div class="upload-icon">📁</div>
                        <div class="upload-text"><?php esc_html_e('Drag & drop files here or click to browse', 'claim-desk'); ?></div>
                        <div class="upload-hint"><?php esc_html_e('Supports: JPG, PNG, GIF (Max 2MB per file)', 'claim-desk'); ?></div>
                        <input type="file" id="fileInput" multiple accept="image/*" style="display: none;">
                    </div>
                    <div class="error-message" id="fileUploadError" style="display: none;"></div>
                    <div class="file-preview" id="filePreview"></div>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="productCondition"><?php esc_html_e('Product Condition', 'claim-desk'); ?></label>
                    <select class="form-select" id="productCondition">
                        <option value=""><?php esc_html_e('Select condition', 'claim-desk'); ?></option>
                        <option value="damaged">Damaged</option>
                        <option value="defective">Defective</option>
                        <option value="used-good">Used - Good Condition</option>
                        <option value="used-worn">Used - Shows Wear</option>
                        <option value="unopened">Unopened/New</option>
                    </select>
                    <div class="error-message"><?php esc_html_e('Please select product condition', 'claim-desk'); ?></div>
                </div>
            </div>

            <!-- Conditional: Exchange Options -->
            <div class="form-section" id="exchangeOptions" style="display: none;">
                <h2 class="section-title"><?php esc_html_e('Replacement Preferences', 'claim-desk'); ?></h2>
                
                <div class="form-group">
                    <label class="form-label" for="replacementSize"><?php esc_html_e('Size', 'claim-desk'); ?></label>
                    <select class="form-select" id="replacementSize">
                        <option value=""><?php esc_html_e('Select size', 'claim-desk'); ?></option>
                        <option value="small">Small</option>
                        <option value="medium">Medium</option>
                        <option value="large">Large</option>
                        <option value="xl">Extra Large</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="replacementColor"><?php esc_html_e('Color', 'claim-desk'); ?></label>
                    <select class="form-select" id="replacementColor">
                        <option value=""><?php esc_html_e('Select color', 'claim-desk'); ?></option>
                        <option value="black">Black</option>
                        <option value="white">White</option>
                        <option value="blue">Blue</option>
                        <option value="red">Red</option>
                    </select>
                </div>
            </div>

            <!-- Conditional: Return Options -->
            <div class="form-section" id="returnOptions" style="display: none;">
                <h2 class="section-title"><?php esc_html_e('Refund Preference', 'claim-desk'); ?></h2>
                
                <div class="form-group">
                    <label class="form-label required" for="refundMethod"><?php esc_html_e('Refund Method', 'claim-desk'); ?></label>
                    <select class="form-select" id="refundMethod">
                        <option value=""><?php esc_html_e('Select refund method', 'claim-desk'); ?></option>
                        <option value="original"><?php esc_html_e('Original Payment Method', 'claim-desk'); ?></option>
                        <option value="store-credit"><?php esc_html_e('Store Credit', 'claim-desk'); ?></option>
                        <option value="bank-transfer"><?php esc_html_e('Bank Transfer', 'claim-desk'); ?></option>
                    </select>
                    <div class="error-message"><?php esc_html_e('Please select refund method', 'claim-desk'); ?></div>
                </div>
            </div>

            <div class="nav-buttons">
                <button class="btn btn-secondary" id="step2Back">
                    <span>←</span>
                    <?php esc_html_e('Back', 'claim-desk'); ?>
                </button>
                <button class="btn btn-primary" id="step2Next" disabled>
                    <?php esc_html_e('Next', 'claim-desk'); ?>
                    <span>→</span>
                </button>
            </div>
        </div>

        <!-- STEP 3: Review & Submit -->
        <div class="step-content" id="step3">
            <div class="card-header">
                <h1 class="card-title"><?php esc_html_e('Review Your Claim', 'claim-desk'); ?></h1>
                <p class="card-subtitle"><?php esc_html_e('Please verify all information before submitting', 'claim-desk'); ?></p>
            </div>

            <div class="form-section">
                <h2 class="section-title"><?php esc_html_e('Claim Summary', 'claim-desk'); ?></h2>
                
                <div class="summary-section">
                    <div class="summary-row">
                        <div class="summary-label"><?php esc_html_e('Selected Products', 'claim-desk'); ?></div>
                        <div class="summary-value">
                            <div class="summary-product" id="summaryProduct">
                                <!-- Injected via JS -->
                            </div>
                        </div>
                    </div>

                    <div class="summary-row">
                        <div class="summary-label"><?php esc_html_e('Claim Type', 'claim-desk'); ?></div>
                        <div class="summary-value" id="summaryClaimType"></div>
                    </div>

                    <div class="summary-row">
                        <div class="summary-label"><?php esc_html_e('Problem Type', 'claim-desk'); ?></div>
                        <div class="summary-value" id="summaryProblemType"></div>
                    </div>

                    <div class="summary-row">
                        <div class="summary-label"><?php esc_html_e('Description', 'claim-desk'); ?></div>
                        <div class="summary-value" id="summaryDescription"></div>
                    </div>

                    <div class="summary-row">
                        <div class="summary-label"><?php esc_html_e('Product Condition', 'claim-desk'); ?></div>
                        <div class="summary-value" id="summaryCondition"></div>
                    </div>

                    <div class="summary-row">
                        <div class="summary-label"><?php esc_html_e('Uploaded Files', 'claim-desk'); ?></div>
                        <div class="summary-value">
                            <div class="summary-files" id="summaryFiles">
                                <span style="color: #757575; font-size: 13px;"><?php esc_html_e('No files uploaded', 'claim-desk'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="summary-row" id="summaryRefundRow" style="display: none;">
                        <div class="summary-label"><?php esc_html_e('Refund Method', 'claim-desk'); ?></div>
                        <div class="summary-value" id="summaryRefund"></div>
                    </div>

                    <div class="summary-row" id="summaryReplacementRow" style="display: none;">
                        <div class="summary-label"><?php esc_html_e('Replacement Details', 'claim-desk'); ?></div>
                        <div class="summary-value" id="summaryReplacement"></div>
                    </div>
                </div>
            </div>

            <div class="confirmation-box">
                <label class="checkbox-wrapper">
                    <input type="checkbox" class="checkbox-input" id="confirmCheckbox">
                    <span class="checkbox-label"><?php esc_html_e('I confirm that all the information provided above is accurate and complete.', 'claim-desk'); ?></span>
                </label>
            </div>

            <div class="nav-buttons">
                <button class="btn btn-secondary" id="step3Back">
                    <span>←</span>
                    <?php esc_html_e('Back', 'claim-desk'); ?>
                </button>
                <button class="btn btn-submit" id="submitBtn" disabled>
                    <?php esc_html_e('Submit Claim', 'claim-desk'); ?>
                    <span>✓</span>
                </button>
            </div>
        </div>

        <!-- Success Screen -->
        <div class="success-screen" id="successScreen">
            <div class="success-icon">✓</div>
            <h1 class="success-title"><?php esc_html_e('Claim Submitted Successfully!', 'claim-desk'); ?></h1>
            <p class="success-message"><?php esc_html_e('Your claim has been received and is being processed.', 'claim-desk'); ?></p>
            <div class="claim-id"><?php esc_html_e('Claim ID:', 'claim-desk'); ?> <span id="generatedClaimId"></span></div>
            <p class="success-message"><?php esc_html_e('We\'ll send you an email confirmation shortly with next steps.', 'claim-desk'); ?></p>
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="button"><?php esc_html_e('Back to Orders', 'claim-desk'); ?></a>
        </div>
    </div>
</div>

<!-- Render CSS Styles inline or enqueue from separate file. 
     Since we are in partial, better to enqueue properly, but for speed we put critical CSS here based on user request. 
     (Ideally move to css/claim-desk-public.css)
-->
<style>
    /* Paste the CSS provided by user here, slightly prefixed to avoid global conflicts if needed, or keep as is */
    /* ... (CSS content from user) ... */
    .cd-wizard-container {
        font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', 'Roboto', sans-serif;
        background: #f6f7f7;
        color: #1e1e1e;
        line-height: 1.6;
        padding: 20px;
        max-width: 900px;
        margin: 0 auto;
    }
    .cd-wizard-container .card-title {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .cd-wizard-container * {
        box-sizing: border-box;
    }

    /* Progress Stepper */
    .progress-stepper {
        background: #ffffff;
        padding: 40px 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
    }

    .stepper-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        position: relative;
        margin-bottom: 10px;
    }

    .stepper-line {
        position: absolute;
        top: 24px;
        left: 16.66%;
        right: 16.66%;
        height: 2px;
        background: #dcdcde;
        z-index: 0;
    }

    .stepper-progress {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        background: #2271b1;
        transition: width 0.3s ease;
        width: 0; /* JS updates this */
    }

    .step {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .step-indicator {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #ffffff;
        border: 2px solid #dcdcde;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 16px;
        color: #757575;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }

    .step.active .step-indicator {
        background: #2271b1;
        border-color: #2271b1;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3);
    }

    .step.completed .step-indicator {
        background: #46b450;
        border-color: #46b450;
        color: #ffffff;
    }

    .step-indicator.check::after {
        content: "✓";
        font-size: 20px;
    }

    .step-title {
        font-size: 14px;
        font-weight: 500;
        color: #757575;
        text-align: center;
    }

    .step.active .step-title {
        color: #2271b1;
        font-weight: 600;
    }

    .step.completed .step-title {
        color: #46b450;
    }

    /* Main Card */
    .claim-card {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        padding: 40px;
        margin-bottom: 20px;
    }

    .card-header {
        margin-bottom: 30px;
    }

    .card-subtitle {
        font-size: 14px;
        color: #757575;
    }

    /* Product Cards - Step 1 */
    .product-grid {
        display: grid;
        gap: 16px;
        margin-bottom: 30px;
    }

    .product-card {
        border: 2px solid #dcdcde;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        gap: 20px;
        align-items: center;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }

    .product-card:hover {
        border-color: #2271b1;
        box-shadow: 0 4px 12px rgba(34, 113, 177, 0.15);
    }

    .product-card.selected {
        border-color: #2271b1;
        background: #f0f6fb;
    }

    .product-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #2271b1;
    }

    .product-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 6px;
        background: #f6f7f7;
    }

    .product-info {
        flex: 1;
    }

    .product-name {
        font-size: 16px;
        font-weight: 600;
        color: #1e1e1e;
        margin-bottom: 6px;
    }

    .product-meta {
        font-size: 13px;
        color: #757575;
        margin-bottom: 8px;
    }

    .eligibility-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }

    .badge-eligible {
        background: #e7f5e8;
        color: #46b450;
    }

    .badge-warranty {
        background: #e5f2ff;
        color: #2271b1;
    }

    .badge-not-eligible {
        background: #f6f7f7;
        color: #757575;
    }

    .product-quantity {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 120px;
    }

    .quantity-label {
        font-size: 12px;
        color: #757575;
        font-weight: 500;
    }

    .quantity-select {
        padding: 8px 12px;
        border: 1px solid #dcdcde;
        border-radius: 6px;
        font-size: 14px;
        background: #ffffff;
        cursor: pointer;
        transition: border-color 0.2s;
    }

    .quantity-select:focus {
        outline: none;
        border-color: #2271b1;
        box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1);
    }

    /* Claim Type Cards - Step 2 */
    .claim-type-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
        margin-bottom: 30px;
    }

    .claim-type-card {
        border: 2px solid #dcdcde;
        border-radius: 8px;
        padding: 24px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .claim-type-card:hover {
        border-color: #2271b1;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(34, 113, 177, 0.15);
    }

    .claim-type-card.selected {
        border-color: #2271b1;
        background: #f0f6fb;
        box-shadow: 0 4px 12px rgba(34, 113, 177, 0.2);
    }

    .claim-icon {
        width: 56px;
        height: 56px;
        margin: 0 auto 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        background: #f0f6fb;
        transition: all 0.3s ease;
    }

    .claim-type-card.selected .claim-icon {
        background: #2271b1;
        color: #ffffff;
    }

    .claim-type-title {
        font-size: 16px;
        font-weight: 600;
        color: #1e1e1e;
        margin-bottom: 8px;
    }

    .claim-type-desc {
        font-size: 13px;
        color: #757575;
    }

    /* Form Elements */
    .form-section {
        margin-bottom: 30px;
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e1e1e;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f6f7f7;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #1e1e1e;
        margin-bottom: 8px;
    }

    .form-label.required::after {
        content: " *";
        color: #dc3232;
    }

    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #dcdcde;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
        transition: all 0.2s;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #2271b1;
        box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 120px;
    }

    /* File Upload */
    .file-upload-area {
        border: 2px dashed #dcdcde;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background: #fafafa;
    }

    .file-upload-area:hover {
        border-color: #2271b1;
        background: #f0f6fb;
    }

    .file-upload-area.dragover {
        border-color: #2271b1;
        background: #f0f6fb;
        transform: scale(1.02);
    }

    .upload-icon {
        font-size: 48px;
        margin-bottom: 16px;
        color: #757575;
    }

    .upload-text {
        font-size: 14px;
        color: #1e1e1e;
        margin-bottom: 8px;
    }

    .upload-hint {
        font-size: 12px;
        color: #757575;
    }

    .file-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }

    .file-item {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #dcdcde;
    }

    .file-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .file-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 24px;
        height: 24px;
        background: #dc3232;
        color: #ffffff;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Review Summary - Step 3 */
    .summary-section {
        margin-bottom: 24px;
    }

    .summary-row {
        display: flex;
        padding: 16px 0;
        border-bottom: 1px solid #f6f7f7;
    }

    .summary-row:last-child {
        border-bottom: none;
    }

    .summary-label {
        flex: 0 0 200px;
        font-weight: 600;
        color: #1e1e1e;
        font-size: 14px;
    }

    .summary-value {
        flex: 1;
        color: #757575;
        font-size: 14px;
    }

    .summary-product {
        display: flex;
        gap: 16px;
        align-items: center;
        margin-bottom: 10px;
    }

    .summary-product-image {
        width: 60px;
        height: 60px;
        border-radius: 6px;
        object-fit: cover;
    }

    .summary-files {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .summary-file-thumb {
        width: 60px;
        height: 60px;
        border-radius: 4px;
        object-fit: cover;
        border: 1px solid #dcdcde;
    }

    /* Confirmation Checkbox */
    .confirmation-box {
        background: #f0f6fb;
        border: 1px solid #2271b1;
        border-radius: 8px;
        padding: 20px;
        margin-top: 30px;
    }

    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
    }

    .checkbox-input {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #2271b1;
    }

    .checkbox-label {
        font-size: 14px;
        color: #1e1e1e;
        cursor: pointer;
    }

    /* Navigation Buttons */
    .nav-buttons {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 30px;
        gap: 16px;
    }

    .btn {
        padding: 14px 32px;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background: #2271b1;
        color: #ffffff;
    }

    .btn-primary:hover {
        background: #135e96;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3);
    }

    .btn-primary:disabled {
        background: #dcdcde;
        color: #757575;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .btn-secondary {
        background: #ffffff;
        color: #2271b1;
        border: 2px solid #dcdcde;
    }

    .btn-secondary:hover {
        border-color: #2271b1;
        background: #f0f6fb;
    }

    .btn-submit {
        background: #46b450;
        color: #ffffff;
    }

    .btn-submit:hover {
        background: #3a9d43;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(70, 180, 80, 0.3);
    }

    .btn-submit:disabled {
        background: #dcdcde;
        color: #757575;
        cursor: not-allowed;
    }

    /* Success Screen */
    .success-screen {
        display: none;
        text-align: center;
        padding: 60px 40px;
    }

    .success-screen.active {
        display: block;
    }

    .success-icon {
        width: 80px;
        height: 80px;
        background: #46b450;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        font-size: 48px;
        color: #ffffff;
    }

    .success-title {
        font-size: 28px;
        font-weight: 600;
        color: #1e1e1e;
        margin-bottom: 12px;
    }

    .success-message {
        font-size: 16px;
        color: #757575;
        margin-bottom: 8px;
    }

    .claim-id {
        font-size: 18px;
        font-weight: 600;
        color: #2271b1;
        margin: 24px 0;
    }

    /* Loading State */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .btn-loading::after {
        content: "";
        width: 16px;
        height: 16px;
        border: 2px solid #ffffff;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        margin-left: 8px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Validation */
    .error-message {
        color: #dc3232;
        font-size: 13px;
        margin-top: 6px;
        display: none;
    }

    .form-group.error .form-input,
    .form-group.error .form-select,
    .form-group.error .form-textarea {
        border-color: #dc3232;
    }

    .form-group.error .error-message {
        display: block;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .cd-wizard-container {
            padding: 12px;
        }

        .claim-card {
            padding: 24px 20px;
        }

        .progress-stepper {
            padding: 24px 16px;
        }

        .stepper-wrapper {
            flex-direction: column;
            gap: 20px;
        }

        .stepper-line {
            display: none;
        }

        .step {
            flex-direction: row;
            width: 100%;
            justify-content: flex-start;
            gap: 12px;
        }

        .step-indicator {
            margin-bottom: 0;
        }

        .step-title {
            text-align: left;
        }

        .product-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .product-quantity {
            width: 100%;
        }

        .claim-type-grid {
            grid-template-columns: 1fr;
        }

        .nav-buttons {
            flex-direction: column-reverse;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }

        .summary-row {
            flex-direction: column;
            gap: 8px;
        }

        .summary-label {
            flex: none;
        }
    }

    /* Hidden Steps */
    .step-content {
        display: none;
    }

    .step-content.active {
        display: block;
    }
</style>
