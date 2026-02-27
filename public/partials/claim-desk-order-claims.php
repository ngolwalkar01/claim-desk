<?php
/**
 * Per-product order claim UI.
 *
 * @var WC_Order $order
 * @var array    $claim_items
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$claim_desk_conditions = (array) Claim_Desk_Config_Manager::get_conditions();
if ( empty( $claim_desk_conditions ) ) {
	$claim_desk_conditions = array(
		array(
			'value' => 'unopened',
			'label' => __( 'Unopened', 'claim-desk' ),
		),
		array(
			'value' => 'opened',
			'label' => __( 'Opened', 'claim-desk' ),
		),
		array(
			'value' => 'damaged',
			'label' => __( 'Damaged', 'claim-desk' ),
		),
	);
}
?>
<section id="cd-order-claims" class="cd-order-claims" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
	<h3 class="cd-order-claims__title"><?php esc_html_e( 'Start a Claim', 'claim-desk' ); ?></h3>
	<p class="cd-order-claims__subtitle"><?php esc_html_e( 'Create a separate claim for each product.', 'claim-desk' ); ?></p>

	<div class="cd-order-claims__list" role="list">
		<?php foreach ( $claim_items as $claim_item ) : ?>
			<?php
			$has_available  = $claim_item['qty_available'] > 0;
			$claim_status   = isset( $claim_item['claim_status'] ) ? sanitize_key( $claim_item['claim_status'] ) : '';
			$window_allows_claims = ! empty( $claim_item['window_allows_claims'] );
			$window_message = isset( $claim_item['window_message'] ) ? sanitize_text_field( $claim_item['window_message'] ) : '';
			$badge_class    = 'is-not-eligible';
			$badge_text     = __( 'Not Eligible', 'claim-desk' );
			$locked_by_status = in_array( $claim_status, array( 'pending', 'approved', 'rejected' ), true );
			$can_start_claim  = $has_available && ! $locked_by_status && $window_allows_claims;

			if ( 'pending' === $claim_status ) {
				$badge_class = 'is-pending';
				$badge_text  = __( 'Already claimed waiting for the merchant response', 'claim-desk' );
			} elseif ( 'approved' === $claim_status ) {
				$badge_class = 'is-approved';
				$badge_text  = __( 'Approved', 'claim-desk' );
			} elseif ( 'rejected' === $claim_status ) {
				$badge_class = 'is-rejected';
				$badge_text  = __( 'Rejected', 'claim-desk' );
			} elseif ( $has_available && $window_allows_claims ) {
				$badge_class = 'is-eligible';
				$badge_text  = __( 'Eligible', 'claim-desk' );
			}
			?>
			<div
				class="cd-claim-row<?php echo $can_start_claim ? '' : ' is-locked'; ?>"
				role="listitem"
				data-order-item-id="<?php echo esc_attr( $claim_item['order_item_id'] ); ?>"
				data-product-id="<?php echo esc_attr( $claim_item['product_id'] ); ?>"
				data-product-name="<?php echo esc_attr( $claim_item['name'] ); ?>"
				data-product-image="<?php echo esc_url( $claim_item['image'] ); ?>"
				data-qty-available="<?php echo esc_attr( $claim_item['qty_available'] ); ?>"
				data-claim-status="<?php echo esc_attr( $claim_status ); ?>"
				data-can-claim="<?php echo $can_start_claim ? '1' : '0'; ?>"
			>
				<div class="cd-claim-row__product">
					<img class="cd-claim-row__image" src="<?php echo esc_url( $claim_item['image'] ); ?>" alt="">
					<div>
						<div class="cd-claim-row__name"><?php echo esc_html( $claim_item['name'] ); ?></div>
						<div class="cd-claim-row__meta">
							<?php
							printf(
								/* translators: 1: purchased qty, 2: available qty */
								esc_html__( 'Purchased: %1$d | Available to claim: %2$d', 'claim-desk' ),
								absint( $claim_item['qty_total'] ),
								absint( $claim_item['qty_available'] )
							);
							?>
						</div>
						<div class="cd-claim-row__badges">
							<span class="cd-status-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_text ); ?></span>
						</div>
					</div>
				</div>

				<div class="cd-claim-row__actions">
					<label class="screen-reader-text" for="cd-qty-<?php echo esc_attr( $claim_item['order_item_id'] ); ?>">
						<?php esc_html_e( 'Claim quantity', 'claim-desk' ); ?>
					</label>
					<select
						id="cd-qty-<?php echo esc_attr( $claim_item['order_item_id'] ); ?>"
						class="cd-claim-qty"
						<?php disabled( ! $can_start_claim ); ?>
					>
						<option value="0"><?php esc_html_e( 'Select qty', 'claim-desk' ); ?></option>
						<?php for ( $qty = 1; $qty <= $claim_item['qty_available']; $qty++ ) : ?>
							<option value="<?php echo esc_attr( $qty ); ?>"><?php echo esc_html( $qty ); ?></option>
						<?php endfor; ?>
					</select>

					<button type="button" class="button button-primary cd-start-claim" disabled>
						<?php esc_html_e( 'Start Claim', 'claim-desk' ); ?>
					</button>
				</div>

				<div class="cd-claim-row__notice" aria-live="polite">
					<?php if ( 'pending' === $claim_status ) : ?>
						<span class="cd-claim-row__notice--success"><?php esc_html_e( 'Already claimed waiting for the merchant response.', 'claim-desk' ); ?></span>
					<?php elseif ( 'approved' === $claim_status ) : ?>
						<span class="cd-claim-row__notice--success"><?php esc_html_e( 'Claim approved by merchant.', 'claim-desk' ); ?></span>
					<?php elseif ( 'rejected' === $claim_status ) : ?>
						<span class="cd-claim-row__notice--error"><?php esc_html_e( 'Claim rejected by merchant.', 'claim-desk' ); ?></span>
					<?php elseif ( ! $window_allows_claims && $window_message ) : ?>
						<span class="cd-claim-row__notice--error"><?php echo esc_html( $window_message ); ?></span>
					<?php elseif ( ! $has_available ) : ?>
						<span class="cd-claim-row__notice--success"><?php esc_html_e( 'Claim already submitted for this product.', 'claim-desk' ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="cd-claim-modal" id="cd-claim-modal" aria-hidden="true">
		<div class="cd-claim-modal__overlay cd-modal-close"></div>
		<div class="cd-claim-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="cd-claim-modal-title">
			<button type="button" class="cd-claim-modal__close cd-modal-close" aria-label="<?php esc_attr_e( 'Close', 'claim-desk' ); ?>">&times;</button>
			<h4 id="cd-claim-modal-title"><?php esc_html_e( 'Claim Details', 'claim-desk' ); ?></h4>

			<div class="cd-claim-modal__body">
				<div class="cd-form-row">
					<label><?php esc_html_e( 'Select Claim Type', 'claim-desk' ); ?></label>
					<input type="hidden" id="cd-claim-type" required>
					<div class="cd-claim-type-cards" role="radiogroup" aria-label="<?php esc_attr_e( 'Select claim type', 'claim-desk' ); ?>">
						<button
							type="button"
							class="cd-claim-type-card"
							data-value="return"
							data-label="<?php esc_attr_e( 'Return', 'claim-desk' ); ?>"
							role="radio"
							aria-checked="false"
						>
							<span class="cd-claim-type-card__icon" aria-hidden="true">&#8635;</span>
							<span class="cd-claim-type-card__content">
								<span class="cd-claim-type-card__title"><?php esc_html_e( 'Return', 'claim-desk' ); ?></span>
								<span class="cd-claim-type-card__detail"><?php esc_html_e( 'Send item back and get your refund.', 'claim-desk' ); ?></span>
							</span>
						</button>
						<button
							type="button"
							class="cd-claim-type-card"
							data-value="exchange"
							data-label="<?php esc_attr_e( 'Exchange', 'claim-desk' ); ?>"
							role="radio"
							aria-checked="false"
						>
							<span class="cd-claim-type-card__icon" aria-hidden="true">&#8644;</span>
							<span class="cd-claim-type-card__content">
								<span class="cd-claim-type-card__title"><?php esc_html_e( 'Exchange', 'claim-desk' ); ?></span>
								<span class="cd-claim-type-card__detail"><?php esc_html_e( 'Replace with another unit or size.', 'claim-desk' ); ?></span>
							</span>
						</button>
					</div>
				</div>

				<div class="cd-form-row">
					<label for="cd-problem-type"><?php esc_html_e( 'Problem Type', 'claim-desk' ); ?></label>
					<select id="cd-problem-type" required>
						<option value=""><?php esc_html_e( 'Select problem type', 'claim-desk' ); ?></option>
						<?php foreach ( (array) Claim_Desk_Config_Manager::get_problems() as $problem ) : ?>
							<option value="<?php echo esc_attr( $problem['value'] ); ?>"><?php echo esc_html( $problem['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="cd-form-row">
					<label for="cd-problem-description"><?php esc_html_e( 'Description', 'claim-desk' ); ?></label>
					<textarea id="cd-problem-description" rows="4" required></textarea>
				</div>

				<div class="cd-form-row">
					<label for="cd-claim-images"><?php esc_html_e( 'Upload Image', 'claim-desk' ); ?></label>
					<input type="file" id="cd-claim-images" accept="image/*" multiple hidden>
					<button type="button" class="cd-upload-card" id="cd-open-image-upload">
						<span class="cd-upload-card__icon" aria-hidden="true">&#128247;</span>
						<span class="cd-upload-card__content">
							<span class="cd-upload-card__title"><?php esc_html_e( 'Choose images', 'claim-desk' ); ?></span>
							<span class="cd-upload-card__detail"><?php esc_html_e( 'Upload clear photos of the issue. Up to 5 images, 2MB each.', 'claim-desk' ); ?></span>
						</span>
					</button>
					<div id="cd-claim-files-preview" class="cd-claim-files-preview"></div>
				</div>

				<div class="cd-form-row">
					<label for="cd-product-condition"><?php esc_html_e( 'Product Condition', 'claim-desk' ); ?></label>
					<select id="cd-product-condition" required>
						<option value=""><?php esc_html_e( 'Select condition', 'claim-desk' ); ?></option>
						<?php foreach ( $claim_desk_conditions as $condition ) : ?>
							<option value="<?php echo esc_attr( isset( $condition['value'] ) ? $condition['value'] : '' ); ?>"><?php echo esc_html( isset( $condition['label'] ) ? $condition['label'] : '' ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="cd-form-row">
					<label for="cd-refund-method"><?php esc_html_e( 'Refund Method', 'claim-desk' ); ?></label>
					<select id="cd-refund-method" required>
						<option value=""><?php esc_html_e( 'Select refund method', 'claim-desk' ); ?></option>
						<option value="original"><?php esc_html_e( 'Original Payment Method', 'claim-desk' ); ?></option>
						<option value="store-credit"><?php esc_html_e( 'Store Credit', 'claim-desk' ); ?></option>
						<option value="bank-transfer"><?php esc_html_e( 'Bank Transfer', 'claim-desk' ); ?></option>
					</select>
				</div>

				<div class="cd-claim-review">
					<h5><?php esc_html_e( 'Review Summary', 'claim-desk' ); ?></h5>
					<p><strong><?php esc_html_e( 'Product:', 'claim-desk' ); ?></strong> <span id="cd-review-product">-</span></p>
					<p><strong><?php esc_html_e( 'Quantity:', 'claim-desk' ); ?></strong> <span id="cd-review-qty">-</span></p>
					<p><strong><?php esc_html_e( 'Claim Type:', 'claim-desk' ); ?></strong> <span id="cd-review-claim-type">-</span></p>
					<p><strong><?php esc_html_e( 'Problem Type:', 'claim-desk' ); ?></strong> <span id="cd-review-problem">-</span></p>
					<p><strong><?php esc_html_e( 'Product Condition:', 'claim-desk' ); ?></strong> <span id="cd-review-condition">-</span></p>
					<p><strong><?php esc_html_e( 'Refund Method:', 'claim-desk' ); ?></strong> <span id="cd-review-refund">-</span></p>
				</div>

				<div id="cd-claim-error" class="cd-claim-error" aria-live="polite"></div>
			</div>

			<div class="cd-claim-modal__footer">
				<button type="button" class="button cd-modal-close"><?php esc_html_e( 'Cancel', 'claim-desk' ); ?></button>
				<button type="button" class="button button-primary" id="cd-submit-claim"><?php esc_html_e( 'Submit Claim', 'claim-desk' ); ?></button>
			</div>
		</div>
	</div>
</section>
