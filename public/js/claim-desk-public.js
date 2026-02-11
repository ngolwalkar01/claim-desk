(function ($) {
    'use strict';

    const ClaimDeskWizard = {
        orderId: 0,
        currentStep: 1,
        selectedItems: {}, // { item_id: qty }
        uploadedFiles: [],
        claimType: '',

        init: function () {
            // Check if wizard exists
            if ($('.cd-wizard-container').length === 0) {
                return;
            }

            // Get Order ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            this.orderId = urlParams.get('order_id');

            if (!this.orderId) {
                console.error('Claim Desk: Missing Order ID');
                return;
            }

            this.bindEvents();
            this.renderConfig(); // New method
            this.fetchItems();
        },

        // Render configuration from backend
        renderConfig: function () {
            if (typeof claim_desk_public === 'undefined') return;

            // 1. Resolutions
            if (claim_desk_public.resolutions) {
                const res = claim_desk_public.resolutions;
                $('.claim-type-card[data-claim-type="return"]').toggle(!!res.return);
                $('.claim-type-card[data-claim-type="exchange"]').toggle(!!res.exchange);
                $('.claim-type-card[data-claim-type="coupon"]').toggle(!!res.coupon);
            }

            // 2. Problem Types
            if (claim_desk_public.problems && Array.isArray(claim_desk_public.problems)) {
                const $select = $('#problemType');
                $select.find('option:not(:first)').remove(); // Keep "Select..."
                claim_desk_public.problems.forEach(p => {
                    $select.append(new Option(p.label, p.value));
                });
            }

            // 3. Product Conditions
            if (claim_desk_public.conditions && Array.isArray(claim_desk_public.conditions)) {
                const $select = $('#productCondition');
                $select.find('option:not(:first)').remove();
                claim_desk_public.conditions.forEach(c => {
                    $select.append(new Option(c.label, c.value));
                });
            }
        },

        fetchItems: function () {
            const $grid = $('#cd-product-grid');

            $.post(claim_desk_public.ajax_url, {
                action: 'claim_desk_get_order_items',
                nonce: claim_desk_public.nonce,
                order_id: this.orderId
            }, (res) => {
                console.log('Claim Desk Debug Response:', res);
                if (res.success) {
                    $grid.empty();
                    // Handle new response structure (items is inside data.items)
                    const items = res.data.items ? res.data.items : res.data;

                    if (res.data.debug) {
                        console.log('Claim Desk Server Debug:', res.data.debug);
                        console.log('Claim Desk Raw Claims:', res.data.raw_claims);
                    }

                    if (Array.isArray(items)) {
                        items.forEach(item => {
                            this.renderProductCard(item, $grid);
                        });
                    } else {
                        console.error('Expected array of items, got:', items);
                    }
                } else {
                    $grid.html('<p class="error-message" style="display:block;">' + res.data + '</p>');
                }
            });
        },

        bindEvents: function () {
            const self = this;

            // Step 1: Next
            $('#step1Next').on('click', function () {
                self.goToStep(2);
            });

            // ... (rest of bindEvents unchanged) ...

            // Input Validation Step 2
            $('#problemType, #problemDescription, #productCondition, #refundMethod').on('change input', function () {
                self.validateStep2();
            });

            // ...
        },

        // ...

        validateStep1: function () {
            const hasItems = Object.keys(this.selectedItems).length > 0;
            $('#step1Next').prop('disabled', !hasItems);

            // Removed hardcoded problem types population from here
        },

        validateStep2: function () {
            const problemType = $('#problemType').val();
            const description = $('#problemDescription').val();
            const condition = $('#productCondition').val();
            let isValid = this.claimType && problemType && description && condition;

            if (this.claimType === 'return') {
                isValid = isValid && $('#refundMethod').val();
            }

            $('#step2Next').prop('disabled', !isValid);
        },

        handleFiles: function (files) {
            const self = this;
            const $preview = $('#filePreview');

            Array.from(files).forEach(file => {
                if (self.uploadedFiles.length >= 5) {
                    alert('Max 5 files');
                    return;
                }
                self.uploadedFiles.push(file);

                const reader = new FileReader();
                reader.onload = (e) => {
                    const idx = self.uploadedFiles.length - 1;
                    const html = `
                        <div class="file-item">
                            <img src="${e.target.result}">
                            <button class="file-remove" data-idx="${idx}">×</button>
                        </div>
                    `;
                    $preview.append(html);
                };
                reader.readAsDataURL(file);
            });

            // Re-bind remove
            $('.file-remove').off('click').on('click', function () {
                // For MVP visual removal only, logic to remove from array requires re-render
                $(this).parent().remove();
            });
        },

        updateSummary: function () {
            // Products
            const $prodSummary = $('#summaryProduct');
            $prodSummary.empty();
            $.each(this.selectedItems, function (id, data) {
                $prodSummary.append(`
                    <div class="summary-product">
                        <img src="${data.image}" class="summary-product-image">
                        <div>
                            <div style="font-weight:600;">${data.name}</div>
                            <div style="font-size:13px;">Qty: ${data.qty}</div>
                        </div>
                    </div>
                 `);
            });

            $('#summaryClaimType').text(this.capitalize(this.claimType));
            $('#summaryProblemType').text($('#problemType option:selected').text());
            $('#summaryDescription').text($('#problemDescription').val());
            $('#summaryCondition').text($('#productCondition option:selected').text());

            if (this.claimType === 'return') {
                $('#summaryRefundRow').show();
                $('#summaryRefund').text($('#refundMethod option:selected').text());
                $('#summaryReplacementRow').hide();
            } else if (this.claimType === 'exchange') {
                $('#summaryReplacementRow').show();
                const size = $('#replacementSize').val() || '-';
                const color = $('#replacementColor').val() || '-';
                $('#summaryReplacement').text(`Size: ${size}, Color: ${color}`);
                $('#summaryRefundRow').hide();
            } else {
                $('#summaryRefundRow').hide();
                $('#summaryReplacementRow').hide();
            }
        },

        submitClaim: function () {
            const self = this;
            const $btn = $('#submitBtn');

            $btn.prop('disabled', true).text('Submitting...');

            // Prepare Data
            // We need to map new fields to what backend expects.
            // Backend expects: scope, items (json), form_data (json array of name/value)

            // Construct form_data array
            const formData = [
                { name: 'problem_type', value: $('#problemType').val() },
                { name: 'description', value: $('#problemDescription').val() },
                { name: 'condition', value: $('#productCondition').val() },
                { name: 'resolution_type', value: this.claimType } // New field
            ];

            if (this.claimType === 'return') {
                formData.push({ name: 'refund_method', value: $('#refundMethod').val() });
            }
            if (this.claimType === 'exchange') {
                formData.push({ name: 'replacement_size', value: $('#replacementSize').val() });
                formData.push({ name: 'replacement_color', value: $('#replacementColor').val() });
            }

            // Items payload: { id: qty }
            const itemsPayload = {};
            $.each(this.selectedItems, function (id, data) {
                itemsPayload[id] = data.qty;
            });

            const data = {
                action: 'claim_desk_submit_claim',
                nonce: claim_desk_public.nonce,
                order_id: this.orderId,
                scope: this.claimType, // Using claim type as scope for now
                items: JSON.stringify(itemsPayload),
                form_data: JSON.stringify(formData)
            };

            $.post(claim_desk_public.ajax_url, data, function (res) {
                if (res.success) {
                    $('#generatedClaimId').text('#' + res.data.claim_id);
                    $('.cd-wizard-container .step-content').removeClass('active');
                    $('#successScreen').addClass('active');
                    // Hide stepper
                    $('.progress-stepper').hide();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    alert(res.data);
                    $btn.prop('disabled', false).text('Submit Claim');
                }
            }).fail(function () {
                alert('Server Error');
                $btn.prop('disabled', false).text('Submit Claim');
            });
        },

        goToStep: function (step) {
            this.currentStep = step;
            $('.step-content').removeClass('active');
            $('#step' + step).addClass('active');

            // Stepper UI
            $('.step').removeClass('active completed');
            $('.step-indicator').removeClass('check').text(function () {
                return $(this).parent().data('step');
            });

            $('.step').each(function () {
                const s = $(this).data('step');
                if (s < step) {
                    $(this).addClass('completed');
                    $(this).find('.step-indicator').addClass('check').text('');
                } else if (s === step) {
                    $(this).addClass('active');
                }
            });

            const p = ((step - 1) / 2) * 100;
            $('#stepperProgress').css('width', p + '%');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        capitalize: function (s) {
            if (!s) return '';
            return s.charAt(0).toUpperCase() + s.slice(1);
        }
    };

    $(document).ready(function () {
        ClaimDeskWizard.init();
    });

})(jQuery);
