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
                if (res.success) {
                    $grid.empty();
                    // Handle new response structure (items is inside data.items)
                    const items = res.data.items ? res.data.items : res.data;



                    if (Array.isArray(items)) {
                        items.forEach(item => {
                            this.renderProductCard(item, $grid);
                        });
                    }
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

        bindEvents: function () {
            const self = this;

            // Step 1: Next
            $('#step1Next').on('click', function () {
                self.goToStep(2);
            });

            // Step 2: Back
            $('#step2Back').on('click', function () {
                self.goToStep(1);
            });

            // Step 2: Next
            $('#step2Next').on('click', function () {
                self.updateSummary();
                self.goToStep(3);
            });

            // Step 3: Back
            $('#step3Back').on('click', function () {
                self.goToStep(2);
            });

            // Submit
            $('#submitBtn').on('click', function () {
                self.submitClaim();
            });

            // Claim Type Selection
            $('.claim-type-card').on('click', function () {
                $('.claim-type-card').removeClass('selected');
                $(this).addClass('selected');
                self.claimType = $(this).data('claim-type');

                // Show/Hide Fields
                if (self.claimType === 'exchange') {
                    $('#exchangeOptions').slideDown();
                    $('#returnOptions').slideUp();
                } else if (self.claimType === 'return') {
                    $('#exchangeOptions').slideUp();
                    $('#returnOptions').slideDown();
                } else {
                    $('#exchangeOptions').slideUp();
                    $('#returnOptions').slideUp();
                }
                self.validateStep2();
            });

            // File Upload
            $('#fileUploadArea').on('click', function () {
                $('#fileInput').click();
            });

            $('#fileInput').on('click', function (e) {
                e.stopPropagation();
            });

            $('#fileInput').on('change', function (e) {
                self.handleFiles(e.target.files);
            });

            /* Drag & Drop */
            const $dropArea = $('#fileUploadArea');
            $dropArea.on('dragover', function (e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            $dropArea.on('dragleave drop', function (e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });
            $dropArea.on('drop', function (e) {
                const files = e.originalEvent.dataTransfer.files;
                self.handleFiles(files);
            });

            // Input Validation Step 2
            $('#problemType, #problemDescription, #productCondition, #refundMethod, #replacementSize, #replacementColor').on('change input', function () {
                self.validateStep2();
            });

            // Confirm Checkbox
            $('#confirmCheckbox').on('change', function () {
                $('#submitBtn').prop('disabled', !$(this).is(':checked'));
            });
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
            const $errorMsg = $('#fileUploadError');
            const maxSize = 2 * 1024 * 1024; // 2MB in bytes
            const rejectedFiles = [];

            // Clear previous error messages
            $errorMsg.hide().empty();

            Array.from(files).forEach(file => {
                // Check max 5 files limit
                if (self.uploadedFiles.length >= 5) {
                    $errorMsg.text('Maximum 5 files allowed.').show();
                    return;
                }

                // Check file size (2MB limit)
                if (file.size > maxSize) {
                    const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                    rejectedFiles.push(`${file.name} (${fileSizeMB}MB)`);
                    return; // Skip this file
                }

                // File is valid, add it
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

            // Show error message for rejected files
            if (rejectedFiles.length > 0) {
                const errorText = rejectedFiles.length === 1
                    ? `File ${rejectedFiles[0]} exceeds the 2MB limit and was not added.`
                    : `${rejectedFiles.length} files exceed the 2MB limit: ${rejectedFiles.join(', ')}`;
                $errorMsg.text(errorText).show();
            }

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


            // Create FormData for multipart upload
            const formDataObj = new FormData();
            formDataObj.append('action', 'claim_desk_submit_claim');
            formDataObj.append('nonce', claim_desk_public.nonce);
            formDataObj.append('order_id', this.orderId);
            formDataObj.append('scope', this.claimType);
            formDataObj.append('items', JSON.stringify(itemsPayload));
            formDataObj.append('form_data', JSON.stringify(formData));

            // Append files
            this.uploadedFiles.forEach((file, index) => {
                formDataObj.append('files[]', file);
            });

            $.ajax({
                url: claim_desk_public.ajax_url,
                type: 'POST',
                data: formDataObj,
                processData: false,
                contentType: false,
                success: function (res) {
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
                },
                error: function () {
                    alert('Server Error');
                    $btn.prop('disabled', false).text('Submit Claim');
                }
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
