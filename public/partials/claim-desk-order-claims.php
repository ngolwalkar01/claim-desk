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
?>
<section id="cd-order-claims" class="cd-order-claims" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
	<h3 class="cd-order-claims__title"><?php esc_html_e( 'Start a Claim', 'claim-desk' ); ?></h3>
	<p class="cd-order-claims__subtitle"><?php esc_html_e( 'Create a separate claim for each product.', 'claim-desk' ); ?></p>

	<div class="cd-order-claims__list" role="list">
		<?php foreach ( $claim_items as $claim_item ) : ?>
			<?php
			$has_available = $claim_item['qty_available'] > 0;
			?>
			<div
				class="cd-claim-row<?php echo $has_available ? '' : ' is-locked'; ?>"
				role="listitem"
				data-order-item-id="<?php echo esc_attr( $claim_item['order_item_id'] ); ?>"
				data-product-id="<?php echo esc_attr( $claim_item['product_id'] ); ?>"
				data-product-name="<?php echo esc_attr( $claim_item['name'] ); ?>"
				data-product-image="<?php echo esc_url( $claim_item['image'] ); ?>"
				data-qty-available="<?php echo esc_attr( $claim_item['qty_available'] ); ?>"
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
							<?php if ( $has_available ) : ?>
								<span class="cd-status-badge is-eligible"><?php esc_html_e( 'Eligible', 'claim-desk' ); ?></span>
							<?php else : ?>
								<span class="cd-status-badge is-not-eligible"><?php esc_html_e( 'Not Eligible', 'claim-desk' ); ?></span>
							<?php endif; ?>
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
						<?php disabled( ! $has_available ); ?>
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
					<?php if ( ! $has_available ) : ?>
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
					<label for="cd-claim-type"><?php esc_html_e( 'Claim Type', 'claim-desk' ); ?></label>
					<select id="cd-claim-type" required>
						<option value=""><?php esc_html_e( 'Select claim type', 'claim-desk' ); ?></option>
						<option value="return"><?php esc_html_e( 'Return', 'claim-desk' ); ?></option>
						<option value="exchange"><?php esc_html_e( 'Exchange', 'claim-desk' ); ?></option>
					</select>
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
					<label for="cd-product-condition"><?php esc_html_e( 'Product Condition', 'claim-desk' ); ?></label>
					<select id="cd-product-condition" required>
						<option value=""><?php esc_html_e( 'Select condition', 'claim-desk' ); ?></option>
						<?php foreach ( (array) Claim_Desk_Config_Manager::get_conditions() as $condition ) : ?>
							<option value="<?php echo esc_attr( $condition['value'] ); ?>"><?php echo esc_html( $condition['label'] ); ?></option>
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

				<div class="cd-form-row">
					<label for="cd-claim-images"><?php esc_html_e( 'Upload Images', 'claim-desk' ); ?></label>
					<input type="file" id="cd-claim-images" accept="image/*" multiple>
					<div id="cd-claim-files-preview" class="cd-claim-files-preview"></div>
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
