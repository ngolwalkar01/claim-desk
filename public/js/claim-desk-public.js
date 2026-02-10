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
        formData: {},

        init: function () {
            this.config = claim_desk_public.scopes;
            this.bindEvents();
        },

        bindEvents: function () {
            const self = this;

            // Open Modal
            $(document).on('click', '.claim-desk-trigger, .claim-desk-file, a[href^="#claim-order-"]', function (e) {
                console.log('Claim Desk: Click detected', this);
                e.preventDefault();
                const href = $(this).attr('href');
                if (href && href.indexOf('#claim-order-') !== -1) {
                    self.currentOrder = href.split('-').pop();
                    console.log('Claim Desk: Opening modal for order', self.currentOrder);
                    self.openModal();
                } else {
                    console.warn('Claim Desk: Invalid href', href);
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

            // Step 2: Item Checkbox & Qty
            $(document).on('change', '.cd-item-checkbox', function () {
                const $row = $(this).closest('.cd-item-select-row');
                const $qtyWrapper = $row.find('.cd-item-qty-wrapper');

                if ($(this).is(':checked')) {
                    $qtyWrapper.removeClass('cd-hidden');
                    self.updateSelection($row);
                } else {
                    $qtyWrapper.addClass('cd-hidden');
                    const id = $row.data('id');
                    delete self.selectedItems[id];
                }
                self.updateButtons();
            });

            $(document).on('change input', '.cd-item-qty-input', function () {
                const $row = $(this).closest('.cd-item-select-row');
                self.updateSelection($row);
            });

            // Step 3: Form Input Changes (to enable Submit)
            $(document).on('change input', '#cd-details-form input, #cd-details-form textarea', function () {
                // Future validation logic
            });

            // Back Button
            $('.cd-modal-back').on('click', function () {
                if (!$('#cd-step-items').hasClass('cd-hidden')) {
                    self.showStep('scope');
                } else if (!$('#cd-step-details').hasClass('cd-hidden')) {
                    self.showStep('items');
                }
            });

            // Next Button (Step 2 -> 3)
            $('.cd-modal-next').on('click', function () {
                if (!$('#cd-step-items').hasClass('cd-hidden')) {
                    self.renderStep3();
                    self.showStep('details');
                }
            });

            // Submit Button
            $('.cd-modal-submit').on('click', function () {
                self.submitClaim();
            });
        },

        openModal: function () {
            $('.cd-modal-overlay').addClass('is-open');
            this.showStep('scope');
            this.renderScopes();
        },

        closeModal: function () {
            $('.cd-modal-overlay').removeClass('is-open');
            this.selectedItems = {};
            this.currentScope = null;
            $('#cd-details-form')[0].reset();
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

        renderStep3: function () {
            const scopeConfig = this.config[this.currentScope];
            const $reasons = $('#cd-reasons-container');
            const $fields = $('#cd-fields-container');

            $reasons.empty();
            $fields.empty();

            // Render Reasons
            if (scopeConfig.reasons && scopeConfig.reasons.length > 0) {
                scopeConfig.reasons.forEach(reason => {
                    const html = `
                        <label class="cd-reason-chip">
                            <input type="radio" name="claim_reason" value="${reason.slug}">
                            <span>${reason.label}</span>
                        </label>
                    `;
                    $reasons.append(html);
                });
            } else {
                $reasons.html('<p>No specific reasons defined.</p>');
            }

            // Render Fields
            if (scopeConfig.fields && scopeConfig.fields.length > 0) {
                scopeConfig.fields.forEach(field => {
                    let tmplId = field.type === 'textarea' ? '#tmpl-cd-field-textarea' : '#tmpl-cd-field-text';
                    let tmpl = $(tmplId).html();
                    let html = tmpl.replace(/{{label}}/g, field.label)
                        .replace(/{{slug}}/g, field.slug)
                        .replace(/{{type}}/g, field.type) // for text/number
                        .replace(/{{required}}/g, field.required ? 'required' : '')
                        .replace(/{{required_mark}}/g, field.required ? '<span class="cd-req">*</span>' : '');
                    $fields.append(html);
                });
            }
        },

        updateSelection: function ($row) {
            const id = $row.data('id');
            const qty = parseInt($row.find('.cd-item-qty-input').val());
            const max = parseInt($row.find('.cd-item-qty-input').attr('max'));

            let finalQty = qty;
            if (qty > max) finalQty = max;
            if (qty < 1) finalQty = 1;

            this.selectedItems[id] = finalQty;
        },

        updateButtons: function () {
            const hasSelection = Object.keys(this.selectedItems).length > 0;
            if (!$('#cd-step-items').hasClass('cd-hidden')) {
                // On Step 2
                $('.cd-modal-next').prop('disabled', !hasSelection);
            }
        },

        showStep: function (stepName) {
            $('.cd-step-view').addClass('cd-hidden');
            $('.cd-modal-back, .cd-modal-next, .cd-modal-submit').addClass('cd-hidden');

            $(`#cd-step-${stepName}`).removeClass('cd-hidden');

            if (stepName === 'scope') {
                // No nav
            }
            if (stepName === 'items') {
                $('.cd-modal-back').removeClass('cd-hidden');
                $('.cd-modal-next').removeClass('cd-hidden').prop('disabled', Object.keys(this.selectedItems).length === 0);
            }
            if (stepName === 'details') {
                $('.cd-modal-back').removeClass('cd-hidden');
                $('.cd-modal-submit').removeClass('cd-hidden');
            }
        },

        submitClaim: function () {
            const self = this;
            const $btn = $('.cd-modal-submit');

            $btn.prop('disabled', true).text('Submitting...');

            const data = {
                action: 'claim_desk_submit_claim',
                nonce: claim_desk_public.nonce,
                order_id: this.currentOrder,
                scope: this.currentScope,
                items: JSON.stringify(this.selectedItems),
                form_data: JSON.stringify($('#cd-details-form').serializeArray())
            };

            $.post(claim_desk_public.ajax_url, data, function (res) {
                $btn.prop('disabled', false).text('Submit Claim');

                if (res.success) {
                    alert(res.data.message);
                    self.closeModal();
                    // Optional: reload to see status if we implement that
                    // location.reload(); 
                } else {
                    alert('Error: ' + res.data);
                }
            }).fail(function () {
                $btn.prop('disabled', false).text('Submit Claim');
                alert('Server error. Please try again.');
            });
        }
    };

    $(document).ready(function () {
        ClaimDesk.init();
    });

})(jQuery);
