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
            this.fetchItems();
        },

        bindEvents: function () {
            const self = this;

            // Step 1: Next
            $('#step1Next').on('click', function () {
                self.goToStep(2);
            });

            // Step 2: Next/Back
            $('#step2Next').on('click', function () {
                self.updateSummary();
                self.goToStep(3);
            });
            $('#step2Back').on('click', function () {
                self.goToStep(1);
            });

            // Step 3: Submit/Back
            $('#step3Back').on('click', function () {
                self.goToStep(2);
            });
            $('#submitBtn').on('click', function () {
                self.submitClaim();
            });

            // Claim Type Selection
            $('.claim-type-card').on('click', function () {
                $('.claim-type-card').removeClass('selected');
                $(this).addClass('selected');
                self.claimType = $(this).data('claim-type');

                // Show/Hide sections
                $('#exchangeOptions').toggle(self.claimType === 'exchange');
                $('#returnOptions').toggle(self.claimType === 'return');

                self.validateStep2();
            });

            // Input Validation Step 2
            $('#problemType, #problemDescription, #productCondition, #refundMethod').on('change input', function () {
                self.validateStep2();
            });

            // Confirm Checkbox
            $('#confirmCheckbox').on('change', function () {
                $('#submitBtn').prop('disabled', !$(this).is(':checked'));
            });

            // File Upload UI (Visual only for Phase 2 MVP)
            $('#fileUploadArea').on('click', function () { $('#fileInput').click(); });
            $('#fileInput').on('change', function (e) { self.handleFiles(e.target.files); });

            // Drag and Drop
            const $dropArea = $('#fileUploadArea');
            $dropArea.on('dragover', function (e) { e.preventDefault(); $dropArea.addClass('dragover'); });
            $dropArea.on('dragleave', function () { $dropArea.removeClass('dragover'); });
            $dropArea.on('drop', function (e) {
                e.preventDefault();
                $dropArea.removeClass('dragover');
                self.handleFiles(e.originalEvent.dataTransfer.files);
            });
        },

        fetchItems: function () {
            const $grid = $('#cd-product-grid');

            $.post(claim_desk_public.ajax_url, {
                action: 'claim_desk_get_order_items',
                nonce: claim_desk_public.nonce,
                order_id: this.orderId
            }, (res) => {
                if (res.success) {
                    $grid.empty();
                    res.data.forEach(item => {
                        this.renderProductCard(item, $grid);
                    });
                } else {
                    $grid.html('<p class="error-message" style="display:block;">' + res.data + '</p>');
                }
            });
        },

        renderProductCard: function (item, $container) {
            const self = this;
            const available = parseInt(item.qty_available);
            const isFullyClaimed = available <= 0;

            let badgeHtml = '<span class="eligibility-badge badge-eligible">Eligible</span>';
            if (isFullyClaimed) {
                badgeHtml = '<span class="eligibility-badge badge-not-eligible">Already Claimed</span>';
            }

            const html = `
                <div class="product-card ${isFullyClaimed ? 'disabled' : ''}" data-item-id="${item.id}">
                    <input type="checkbox" class="product-checkbox" ${isFullyClaimed ? 'disabled' : ''}>
                    <img src="${item.image}" alt="${item.name}" class="product-image">
                    <div class="product-info">
                        <div class="product-name">${item.name}</div>
                        <div class="product-meta">Purchased: ${item.qty} | Available: ${available}</div>
                        ${badgeHtml}
                    </div>
                    <div class="product-quantity">
                        <label class="quantity-label">Claim Qty:</label>
                        <select class="quantity-select" disabled>
                            <option value="0">Select</option>
                            ${this.generateQtyOptions(available)}
                        </select>
                    </div>
                </div>
            `;
            const $card = $(html);
            $container.append($card);

            if (isFullyClaimed) return; // No events for claimed items

            // Bind Card Events
            const $checkbox = $card.find('.product-checkbox');
            const $select = $card.find('.quantity-select');

            $card.on('click', function (e) {
                if (e.target !== $checkbox[0] && e.target !== $select[0]) {
                    $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
                }
            });

            $checkbox.on('change', function () {
                if ($(this).is(':checked')) {
                    $card.addClass('selected');
                    $select.prop('disabled', false);
                } else {
                    $card.removeClass('selected');
                    $select.prop('disabled', true).val(0);
                    delete self.selectedItems[item.id];
                }
                self.validateStep1();
            });

            $select.on('change', function () {
                const qty = parseInt($(this).val());
                if (qty > 0) {
                    self.selectedItems[item.id] = {
                        qty: qty,
                        name: item.name,
                        image: item.image
                    };
                } else {
                    delete self.selectedItems[item.id];
                }
                self.validateStep1();
            });
        },

        generateQtyOptions: function (max) {
            let opts = '';
            for (let i = 1; i <= max; i++) {
                opts += `<option value="${i}">${i}</option>`;
            }
            return opts;
        },

        validateStep1: function () {
            const hasItems = Object.keys(this.selectedItems).length > 0;
            $('#step1Next').prop('disabled', !hasItems);

            // Populate Problem Type Options if not already done (could allow dynamic config later)
            if ($('#problemType option').length <= 1) {
                const problems = [
                    { val: 'damaged', txt: 'Product Damaged' },
                    { val: 'defective', txt: 'Product Defective' },
                    { val: 'wrong-item', txt: 'Wrong Item Received' },
                    { val: 'wrong-size', txt: 'Wrong Size/Color' },
                    { val: 'not-as-described', txt: 'Not As Described' },
                    { val: 'quality-issue', txt: 'Quality Issue' },
                    { val: 'other', txt: 'Other' }
                ];
                problems.forEach(p => $('#problemType').append(new Option(p.txt, p.val)));
            }
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
