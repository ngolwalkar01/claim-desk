( function ( $ ) {
	'use strict';

	const ClaimDeskProductFlow = {
		orderId: 0,
		activeRow: null,
		activeData: {},
		uploadedFiles: [],
		lastFocused: null,

		init: function () {
			const $root = $( '#cd-order-claims' );
			if ( ! $root.length || typeof claim_desk_public === 'undefined' ) {
				return;
			}

			this.orderId = parseInt( $root.data( 'order-id' ), 10 ) || 0;
			if ( ! this.orderId ) {
				return;
			}

			this.bindRowEvents();
			this.bindModalEvents();
		},

		bindRowEvents: function () {
			const self = this;

			$( document ).on( 'change', '.cd-claim-qty', function () {
				const $row = $( this ).closest( '.cd-claim-row' );
				const qty = parseInt( $( this ).val(), 10 ) || 0;
				const $button = $row.find( '.cd-start-claim' );
				$button.prop( 'disabled', qty < 1 );
				$button.toggleClass( 'is-active', qty > 0 );
			} );

			$( document ).on( 'click', '.cd-start-claim', function () {
				const $row = $( this ).closest( '.cd-claim-row' );
				const qty = parseInt( $row.find( '.cd-claim-qty' ).val(), 10 ) || 0;

				if ( qty < 1 ) {
					self.setRowNotice( $row, claim_desk_public.i18n.select_quantity, true );
					return;
				}

				self.activeRow = $row;
				self.activeData = {
					order_item_id: parseInt( $row.data( 'order-item-id' ), 10 ) || 0,
					product_id: parseInt( $row.data( 'product-id' ), 10 ) || 0,
					product_name: String( $row.data( 'product-name' ) || '' ),
					quantity: qty
				};

				self.openModal();
			} );
		},

		bindModalEvents: function () {
			const self = this;
			const $modal = $( '#cd-claim-modal' );

			$( document ).on( 'click', '.cd-modal-close', function () {
				self.closeModal();
			} );

			$( document ).on( 'change input', '#cd-claim-type, #cd-problem-type, #cd-problem-description, #cd-product-condition, #cd-refund-method', function () {
				self.updateReview();
			} );

			$( document ).on( 'change', '#cd-claim-images', function ( event ) {
				self.handleFiles( event.target.files );
			} );

			$( document ).on( 'click', '#cd-submit-claim', function () {
				self.submitClaim();
			} );

			$( document ).on( 'keydown', function ( event ) {
				if ( event.key === 'Escape' && $modal.attr( 'aria-hidden' ) === 'false' ) {
					self.closeModal();
				}
			} );
		},

		openModal: function () {
			this.lastFocused = document.activeElement;
			this.resetModalForm();
			this.updateReview();

			const $modal = $( '#cd-claim-modal' );
			$modal.attr( 'aria-hidden', 'false' ).addClass( 'is-open' );
			$( 'body' ).addClass( 'cd-modal-open' );
			$( '#cd-claim-type' ).trigger( 'focus' );
		},

		closeModal: function () {
			const $modal = $( '#cd-claim-modal' );
			$modal.attr( 'aria-hidden', 'true' ).removeClass( 'is-open' );
			$( 'body' ).removeClass( 'cd-modal-open' );
			this.clearModalError();

			if ( this.lastFocused ) {
				$( this.lastFocused ).trigger( 'focus' );
			}
		},

		resetModalForm: function () {
			this.uploadedFiles = [];

			$( '#cd-claim-type' ).val( '' );
			$( '#cd-problem-type' ).val( '' );
			$( '#cd-problem-description' ).val( '' );
			$( '#cd-product-condition' ).val( '' );
			$( '#cd-refund-method' ).val( '' );
			$( '#cd-claim-images' ).val( '' );
			$( '#cd-claim-files-preview' ).empty();
			this.clearModalError();
		},

		handleFiles: function ( files ) {
			const maxSize = 2 * 1024 * 1024;
			const maxFiles = 5;
			const nextFiles = [];
			let errorMessage = '';

			Array.from( files ).forEach( function ( file ) {
				if ( nextFiles.length >= maxFiles ) {
					return;
				}

				if ( file.size > maxSize ) {
					errorMessage = file.name + ' exceeds 2MB.';
					return;
				}

				nextFiles.push( file );
			} );

			this.uploadedFiles = nextFiles;
			this.renderFilePreview();

			if ( errorMessage ) {
				this.showModalError( errorMessage );
			}
		},

		renderFilePreview: function () {
			const $preview = $( '#cd-claim-files-preview' );
			$preview.empty();

			this.uploadedFiles.forEach( function ( file ) {
				$preview.append( '<span class="cd-claim-file-tag">' + ClaimDeskProductFlow.escapeHtml( file.name ) + '</span>' );
			} );
		},

		updateReview: function () {
			$( '#cd-review-product' ).text( this.activeData.product_name || '-' );
			$( '#cd-review-qty' ).text( this.activeData.quantity || '-' );
			$( '#cd-review-claim-type' ).text( $( '#cd-claim-type option:selected' ).text() || '-' );
			$( '#cd-review-problem' ).text( $( '#cd-problem-type option:selected' ).text() || '-' );
			$( '#cd-review-condition' ).text( $( '#cd-product-condition option:selected' ).text() || '-' );
			$( '#cd-review-refund' ).text( $( '#cd-refund-method option:selected' ).text() || '-' );
		},

		validateForm: function () {
			const claimType = String( $( '#cd-claim-type' ).val() || '' );
			const problemType = String( $( '#cd-problem-type' ).val() || '' );
			const description = String( $( '#cd-problem-description' ).val() || '' ).trim();
			const productCondition = String( $( '#cd-product-condition' ).val() || '' );
			const refundMethod = String( $( '#cd-refund-method' ).val() || '' );

			if ( ! claimType || ! problemType || ! description || ! productCondition || ! refundMethod ) {
				this.showModalError( 'Please complete all required fields.' );
				return false;
			}

			if ( ! this.activeData.order_item_id || ! this.activeData.product_id || ! this.activeData.quantity ) {
				this.showModalError( 'Invalid product selection.' );
				return false;
			}

			return true;
		},

		submitClaim: function () {
			const self = this;
			if ( ! this.validateForm() ) {
				return;
			}

			const $button = $( '#cd-submit-claim' );
			const originalText = $button.text();
			this.clearModalError();
			$button.prop( 'disabled', true ).addClass( 'is-loading' ).text( claim_desk_public.i18n.loading );

			const formData = new FormData();
			formData.append( 'action', 'claim_desk_submit_claim' );
			formData.append( 'nonce', claim_desk_public.nonce );
			formData.append( 'order_id', this.orderId );
			formData.append( 'order_item_id', this.activeData.order_item_id );
			formData.append( 'product_id', this.activeData.product_id );
			formData.append( 'quantity', this.activeData.quantity );
			formData.append( 'claim_type', $( '#cd-claim-type' ).val() );
			formData.append( 'problem_type', $( '#cd-problem-type' ).val() );
			formData.append( 'description', $( '#cd-problem-description' ).val() );
			formData.append( 'product_condition', $( '#cd-product-condition' ).val() );
			formData.append( 'refund_method', $( '#cd-refund-method' ).val() );

			this.uploadedFiles.forEach( function ( file ) {
				formData.append( 'files[]', file );
			} );

			$.ajax( {
				url: claim_desk_public.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false
			} ).done( function ( response ) {
				if ( ! response || ! response.success ) {
					const message = response && response.data ? response.data : claim_desk_public.i18n.server_error;
					self.showModalError( message );
					return;
				}

				self.setRowNotice( self.activeRow, response.data.message, false );
				self.activeRow.addClass( 'is-claimed' );
				self.activeRow.find( '.cd-claim-qty' ).prop( 'disabled', true );
				self.activeRow.find( '.cd-start-claim' ).prop( 'disabled', true ).removeClass( 'is-active' );
				self.closeModal();
			} ).fail( function () {
				self.showModalError( claim_desk_public.i18n.server_error );
			} ).always( function () {
				$button.prop( 'disabled', false ).removeClass( 'is-loading' ).text( originalText || claim_desk_public.i18n.submit );
			} );
		},

		setRowNotice: function ( $row, message, isError ) {
			const className = isError ? 'cd-claim-row__notice--error' : 'cd-claim-row__notice--success';
			$row.find( '.cd-claim-row__notice' ).html( '<span class="' + className + '">' + this.escapeHtml( message ) + '</span>' );
		},

		showModalError: function ( message ) {
			$( '#cd-claim-error' ).text( message ).addClass( 'is-visible' );
		},

		clearModalError: function () {
			$( '#cd-claim-error' ).removeClass( 'is-visible' ).empty();
		},

		escapeHtml: function ( text ) {
			return String( text ).replace( /[&<>"']/g, function ( character ) {
				return {
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;',
					"'": '&#39;'
				}[ character ];
			} );
		}
	};

	$( function () {
		ClaimDeskProductFlow.init();
	} );
}( jQuery ) );
