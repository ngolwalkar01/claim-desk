<?php
/**
 * Frontend Wizard HTML
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$claim_desk_order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
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
				<div class="step-title"><?php esc_html_e( 'Select Product', 'claim-desk' ); ?></div>
			</div>

			<div class="step" data-step="2">
				<div class="step-indicator">2</div>
				<div class="step-title"><?php esc_html_e( 'Claim Details', 'claim-desk' ); ?></div>
			</div>

			<div class="step" data-step="3">
				<div class="step-indicator">3</div>
				<div class="step-title"><?php esc_html_e( 'Review & Submit', 'claim-desk' ); ?></div>
			</div>
		</div>
	</div>

	<!-- Main Form Card -->
	<div class="claim-card">
		<!-- Order Info Header -->
		<div class="card-header">
			<h1 class="card-title"><?php
			/* translators: %1$d = Order ID. Numbered placeholder allows translators to reorder text if needed. */
			printf( esc_html__( 'Claim for Order #%1$d', 'claim-desk' ), absint( $claim_desk_order_id ) );
			?></h1>
			<p class="card-subtitle"><?php esc_html_e( 'Follow the steps below to submit your claim.', 'claim-desk' ); ?></p>
		</div>

		<!-- STEP 1: Product Selection -->
		<div class="step-content active" id="step1">
			<div class="card-header">
				<h2 class="section-title"><?php esc_html_e( 'Select Products', 'claim-desk' ); ?></h2>
			</div>

			<div class="product-grid" id="cd-product-grid">
				<!-- Products will be injected here via JS -->
				<p>Loading products...</p>
			</div>

			<div class="nav-buttons">
				<div></div>
				<button class="btn btn-primary" id="step1Next" disabled>
					<?php esc_html_e( 'Next', 'claim-desk' ); ?>
					<span>â†’</span>
				</button>
			</div>
		</div>

		<!-- STEP 2: Claim Details -->
		<div class="step-content" id="step2">

			<!-- Claim Type Selection -->
			<div class="form-section">
				<h2 class="section-title"><?php esc_html_e( 'Select Claim Type', 'claim-desk' ); ?></h2>
				<div class="claim-type-grid">
					<div class="claim-type-card" data-claim-type="return">
						<div class="claim-icon">â†©</div>
						<div class="claim-type-title"><?php esc_html_e( 'Return', 'claim-desk' ); ?></div>
						<div class="claim-type-desc"><?php esc_html_e( 'Get a full refund for your product', 'claim-desk' ); ?></div>
					</div>

					<div class="claim-type-card" data-claim-type="exchange">
						<div class="claim-icon">â‡„</div>
						<div class="claim-type-title"><?php esc_html_e( 'Exchange', 'claim-desk' ); ?></div>
						<div class="claim-type-desc"><?php esc_html_e( 'Replace with same or different product', 'claim-desk' ); ?></div>
					</div>

					<div class="claim-type-card" data-claim-type="coupon">
						<div class="claim-icon">ðŸŽŸ</div>
						<div class="claim-type-title"><?php esc_html_e( 'Discount Coupon', 'claim-desk' ); ?></div>
						<div class="claim-type-desc"><?php esc_html_e( 'Get a discount for store credit', 'claim-desk' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Problem Details -->
			<div class="form-section">
				<h2 class="section-title"><?php esc_html_e( 'Problem Details', 'claim-desk' ); ?></h2>

				<!-- Problem Type (Loaded from Config) -->
				<div class="form-group">
					<label class="form-label required" for="problemType"><?php esc_html_e( 'Problem Type', 'claim-desk' ); ?></label>
					<select class="form-select" id="problemType">
						<option value=""><?php esc_html_e( 'Select a problem type', 'claim-desk' ); ?></option>
						<!-- Options injected via JS -->
					</select>
					<div class="error-message"><?php esc_html_e( 'Please select a problem type', 'claim-desk' ); ?></div>
				</div>

				<div class="form-group">
					<label class="form-label required" for="problemDescription"><?php esc_html_e( 'Describe the Issue', 'claim-desk' ); ?></label>
					<textarea class="form-textarea" id="problemDescription" placeholder="<?php esc_attr_e( 'Please provide details about the issue...', 'claim-desk' ); ?>"></textarea>
					<div class="error-message"><?php esc_html_e( 'Please describe the issue', 'claim-desk' ); ?></div>
				</div>

				<div class="form-group">
					<label class="form-label"><?php esc_html_e( 'Upload Images', 'claim-desk' ); ?></label>
					<div class="file-upload-area" id="fileUploadArea">
						<div class="upload-icon">ðŸ“</div>
						<div class="upload-text"><?php esc_html_e( 'Drag & drop files here or click to browse', 'claim-desk' ); ?></div>
						<div class="upload-hint"><?php esc_html_e( 'Supports: JPG, PNG, GIF (Max 2MB per file)', 'claim-desk' ); ?></div>
						<input type="file" id="fileInput" multiple accept="image/*" style="display: none;">
					</div>
					<div class="error-message" id="fileUploadError" style="display: none;"></div>
					<div class="file-preview" id="filePreview"></div>
				</div>

				<div class="form-group">
					<label class="form-label required" for="productCondition"><?php esc_html_e( 'Product Condition', 'claim-desk' ); ?></label>
					<select class="form-select" id="productCondition">
						<option value=""><?php esc_html_e( 'Select condition', 'claim-desk' ); ?></option>
						<option value="damaged">Damaged</option>
						<option value="defective">Defective</option>
						<option value="used-good">Used - Good Condition</option>
						<option value="used-worn">Used - Shows Wear</option>
						<option value="unopened">Unopened/New</option>
					</select>
					<div class="error-message"><?php esc_html_e( 'Please select product condition', 'claim-desk' ); ?></div>
				</div>
			</div>

			<!-- Conditional: Exchange Options -->
			<div class="form-section" id="exchangeOptions" style="display: none;">
				<h2 class="section-title"><?php esc_html_e( 'Replacement Preferences', 'claim-desk' ); ?></h2>

				<div class="form-group">
					<label class="form-label" for="replacementSize"><?php esc_html_e( 'Size', 'claim-desk' ); ?></label>
					<select class="form-select" id="replacementSize">
						<option value=""><?php esc_html_e( 'Select size', 'claim-desk' ); ?></option>
						<option value="small">Small</option>
						<option value="medium">Medium</option>
						<option value="large">Large</option>
						<option value="xl">Extra Large</option>
					</select>
				</div>

				<div class="form-group">
					<label class="form-label" for="replacementColor"><?php esc_html_e( 'Color', 'claim-desk' ); ?></label>
					<select class="form-select" id="replacementColor">
						<option value=""><?php esc_html_e( 'Select color', 'claim-desk' ); ?></option>
						<option value="black">Black</option>
						<option value="white">White</option>
						<option value="blue">Blue</option>
						<option value="red">Red</option>
					</select>
				</div>
			</div>

			<!-- Conditional: Return Options -->
			<div class="form-section" id="returnOptions" style="display: none;">
				<h2 class="section-title"><?php esc_html_e( 'Refund Preference', 'claim-desk' ); ?></h2>

				<div class="form-group">
					<label class="form-label required" for="refundMethod"><?php esc_html_e( 'Refund Method', 'claim-desk' ); ?></label>
					<select class="form-select" id="refundMethod">
						<option value=""><?php esc_html_e( 'Select refund method', 'claim-desk' ); ?></option>
						<option value="original"><?php esc_html_e( 'Original Payment Method', 'claim-desk' ); ?></option>
						<option value="store-credit"><?php esc_html_e( 'Store Credit', 'claim-desk' ); ?></option>
						<option value="bank-transfer"><?php esc_html_e( 'Bank Transfer', 'claim-desk' ); ?></option>
					</select>
					<div class="error-message"><?php esc_html_e( 'Please select refund method', 'claim-desk' ); ?></div>
				</div>
			</div>

			<div class="nav-buttons">
				<button class="btn btn-secondary" id="step2Back">
					<span>â†</span>
					<?php esc_html_e( 'Back', 'claim-desk' ); ?>
				</button>
				<button class="btn btn-primary" id="step2Next" disabled>
					<?php esc_html_e( 'Next', 'claim-desk' ); ?>
					<span>â†’</span>
				</button>
			</div>
		</div>

		<!-- STEP 3: Review & Submit -->
		<div class="step-content" id="step3">
			<div class="card-header">
				<h1 class="card-title"><?php esc_html_e( 'Review Your Claim', 'claim-desk' ); ?></h1>
				<p class="card-subtitle"><?php esc_html_e( 'Please verify all information before submitting', 'claim-desk' ); ?></p>
			</div>

			<div class="form-section">
				<h2 class="section-title"><?php esc_html_e( 'Claim Summary', 'claim-desk' ); ?></h2>

				<div class="summary-section">
					<div class="summary-row">
						<div class="summary-label"><?php esc_html_e( 'Selected Products', 'claim-desk' ); ?></div>
						<div class="summary-value">
							<div class="summary-product" id="summaryProduct">
								<!-- Injected via JS -->
							</div>
						</div>
					</div>

					<div class="summary-row">
						<div class="summary-label"><?php esc_html_e( 'Claim Type', 'claim-desk' ); ?></div>
						<div class="summary-value" id="summaryClaimType"></div>
					</div>

					<div class="summary-row">
						<div class="summary-label"><?php esc_html_e( 'Problem Type', 'claim-desk' ); ?></div>
						<div class="summary-value" id="summaryProblemType"></div>
					</div>

					<div class="summary-row">
						<div class="summary-label"><?php esc_html_e( 'Description', 'claim-desk' ); ?></div>
						<div class="summary-value" id="summaryDescription"></div>
					</div>

					<div class="summary-row">
						<div class="summary-label"><?php esc_html_e( 'Product Condition', 'claim-desk' ); ?></div>
						<div class="summary-value" id="summaryCondition"></div>
					</div>

					<div class="summary-row">
						<div class="summary-label"><?php esc_html_e( 'Uploaded Files', 'claim-desk' ); ?></div>
						<div class="summary-value">
							<div class="summary-files" id="summaryFiles">
								<span style="color: #757575; font-size: 13px;"><?php esc_html_e( 'No files uploaded', 'claim-desk' ); ?></span>
							</div>
						</div>
					</div>

					<div class="summary-row" id="summaryRefundRow" style="display: none;">
						<div class="summary-label"><?php esc_html_e( 'Refund Method', 'claim-desk' ); ?></div>
						<div class="summary-value" id="summaryRefund"></div>
					</div>

					<div class="summary-row" id="summaryReplacementRow" style="display: none;">
						<div class="summary-label"><?php esc_html_e( 'Replacement Details', 'claim-desk' ); ?></div>
						<div class="summary-value" id="summaryReplacement"></div>
					</div>
				</div>
			</div>

			<div class="confirmation-box">
				<label class="checkbox-wrapper">
					<input type="checkbox" class="checkbox-input" id="confirmCheckbox">
					<span class="checkbox-label"><?php esc_html_e( 'I confirm that all the information provided above is accurate and complete.', 'claim-desk' ); ?></span>
				</label>
			</div>

			<div class="nav-buttons">
				<button class="btn btn-secondary" id="step3Back">
					<span>â†</span>
					<?php esc_html_e( 'Back', 'claim-desk' ); ?>
				</button>
				<button class="btn btn-submit" id="submitBtn" disabled>
					<?php esc_html_e( 'Submit Claim', 'claim-desk' ); ?>
					<span>âœ“</span>
				</button>
			</div>
		</div>

		<!-- Success Screen -->
		<div class="success-screen" id="successScreen">
			<div class="success-icon">âœ“</div>
			<h1 class="success-title"><?php esc_html_e( 'Claim Submitted Successfully!', 'claim-desk' ); ?></h1>
			<p class="success-message"><?php esc_html_e( 'Your claim has been received and is being processed.', 'claim-desk' ); ?></p>
			<div class="claim-id"><?php esc_html_e( 'Claim ID:', 'claim-desk' ); ?> <span id="generatedClaimId"></span></div>
			<p class="success-message"><?php esc_html_e( 'We\'ll send you an email confirmation shortly with next steps.', 'claim-desk' ); ?></p>
			<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="button"><?php esc_html_e( 'Back to Orders', 'claim-desk' ); ?></a>
		</div>
	</div>
</div>
