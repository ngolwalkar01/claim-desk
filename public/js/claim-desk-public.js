(function ($) {
    'use strict';

    /**
     * Frontend Logic
     */
    const ClaimDesk = {
        config: {},
        currentOrder: 0,
        currentScope: null,
        selectedItems: {}, // { item_id: qty }

        init: function () {
            this.config = claim_desk_public.scopes;
            this.bindEvents();
        },

        bindEvents: function () {
            const self = this;

            // Open Modal
            $(document).on('click', '.claim-desk-trigger', function (e) {
                e.preventDefault();
                // Extract Order ID from URL hash '#claim-order-123'
                const href = $(this).attr('href');
                if (href && href.indexOf('#claim-order-') !== -1) {
                    self.currentOrder = href.split('-').pop();
                    self.openModal();
                } else {
                    console.error('Claim Desk: Could not find Order ID');
                }
            });

            // Close
            $('.cd-close-modal, .cd-modal-overlay').on('click', function (e) {
                if (e.target === this || $(this).hasClass('cd-close-modal')) {
                    self.closeModal();
                }
            });

            // Step 1: Scope Click
            $(document).on('click', '.cd-scope-card', function () {
                const slug = $(this).data('slug');
                self.selectScope(slug);
            });

            // Step 2: Item Checkbox
            $(document).on('change', '.cd-item-checkbox', function () {
                const $row = $(this).closest('.cd-item-select-row');
                const $qtyWrapper = $row.find('.cd-item-qty-wrapper');

                if ($(this).is(':checked')) {
                    $qtyWrapper.removeClass('cd-hidden');
                    // Add to selection
                    self.updateSelection($row);
                } else {
                    $qtyWrapper.addClass('cd-hidden');
                    // Remove from selection
                    const id = $row.data('id');
                    delete self.selectedItems[id];
                }
                self.updateButtons();
            });

            // Step 2: Qty Change
            $(document).on('change input', '.cd-item-qty-input', function () {
                const $row = $(this).closest('.cd-item-select-row');
                self.updateSelection($row);
            });

            // Back Button
            $('.cd-modal-back').on('click', function () {
                // Determine current step and go back
                // Simple logic for now: If on Step 2, go to Step 1
                if (!$('#cd-step-items').hasClass('cd-hidden')) {
                    self.showStep('scope');
                }
            });

            // Next Button
            $('.cd-modal-next').on('click', function () {
                // If on Step 2, go to Step 3
                if (!$('#cd-step-items').hasClass('cd-hidden')) {
                    alert('Going to Step 3 (Pending Implementation)');
                }
            });
        },

        openModal: function () {
            $('.cd-modal-overlay').addClass('is-open');
            this.showStep('scope');
            this.renderScopes();
        },

        closeModal: function () {
            $('.cd-modal-overlay').removeClass('is-open');
            // Reset state
            this.selectedItems = {};
            this.currentScope = null;
        },

        renderScopes: function () {
            const $list = $('#cd-scope-list');
            $list.empty();

            if (!this.config || Object.keys(this.config).length === 0) {
                $list.html('<p>No claim types configured.</p>');
                return;
            }

            $.each(this.config, function (key, scope) {
                const html = `
                    <div class="cd-scope-card" data-slug="${scope.slug}">
                        <span class="cd-scope-icon dashicons dashicons-${scope.icon}"></span>
                        <h4>${scope.label}</h4>
                    </div>
                `;
                $list.append(html);
            });
        },

        selectScope: function (slug) {
            this.currentScope = slug;
            this.showStep('items');
            this.fetchItems();
        },

        fetchItems: function () {
            const $container = $('#cd-items-container');
            $container.html('<div class="cd-loading">Loading items...</div>');

            $.post(claim_desk_public.ajax_url, {
                action: 'claim_desk_get_order_items',
                nonce: claim_desk_public.nonce,
                order_id: this.currentOrder
            }, (res) => {
                if (res.success) {
                    this.renderItems(res.data);
                } else {
                    $container.html('<p class="cd-error">' + res.data + '</p>');
                }
            });
        },

        renderItems: function (items) {
            const $container = $('#cd-items-container');
            const tmpl = $('#tmpl-cd-item-row').html();
            $container.empty();

            items.forEach(item => {
                let html = tmpl.replace(/{{id}}/g, item.id)
                    .replace(/{{name}}/g, item.name)
                    .replace(/{{image}}/g, item.image)
                    .replace(/{{price}}/g, item.price)
                    .replace(/{{max_qty}}/g, item.qty);
                $container.append(html);
            });
        },

        updateSelection: function ($row) {
            const id = $row.data('id');
            const qty = parseInt($row.find('.cd-item-qty-input').val());
            const max = parseInt($row.find('.cd-item-qty-input').attr('max'));

            // Validate
            let finalQty = qty;
            if (qty > max) finalQty = max;
            if (qty < 1) finalQty = 1;

            this.selectedItems[id] = finalQty;
        },

        updateButtons: function () {
            // Check if at least one item selected
            const hasSelection = Object.keys(this.selectedItems).length > 0;
            if (hasSelection) {
                $('.cd-modal-next').removeClass('cd-hidden').prop('disabled', false);
            } else {
                // If on step 2, hide/disable next
                if (!$('#cd-step-items').hasClass('cd-hidden')) {
                    $('.cd-modal-next').prop('disabled', true);
                }
            }
        },

        showStep: function (stepName) {
            // Hide all
            $('.cd-step-view').addClass('cd-hidden');
            $('.cd-modal-back, .cd-modal-next').addClass('cd-hidden');

            // Show target
            $(`#cd-step-${stepName}`).removeClass('cd-hidden');

            if (stepName === 'scope') {
                // No back, no next (clicked to advance)
            }
            if (stepName === 'items') {
                $('.cd-modal-back').removeClass('cd-hidden');
                $('.cd-modal-next').removeClass('cd-hidden').prop('disabled', true); // Disabled until selection
            }
        }
    };

    $(document).ready(function () {
        ClaimDesk.init();
    });

})(jQuery);
