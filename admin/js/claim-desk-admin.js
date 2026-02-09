(function ($) {
    'use strict';

    /**
     * Claim Desk Admin Logic
     */
    $(document).ready(function () {
        if ($('#cd-config-container').length) {
            initConfigPage();
        }
    });

    function initConfigPage() {
        const $container = $('#cd-config-container');
        const $saveBtn = $('#cd-save-config');
        const $spinner = $('.cd-header .spinner');

        // Load Config
        loadConfig();

        // Handlers
        $saveBtn.on('click', saveConfig);

        // Add Scope
        $('#cd-add-scope').on('click', function (e) {
            e.preventDefault();
            const slug = prompt("Enter a unique ID for this scope (e.g., 'warranty', 'logistics'):");
            if (slug) {
                const cleanSlug = slug.toLowerCase().replace(/[^a-z0-9_]/g, '');
                renderScope({
                    slug: cleanSlug,
                    label: 'New Scope',
                    icon: 'admin-generic',
                    reasons: [],
                    fields: []
                });
            }
        });

        // Toggle Postbox
        $container.on('click', '.handlediv', function () {
            $(this).closest('.postbox').toggleClass('closed');
        });

        // Remove Scope
        $container.on('click', '.cd-remove-scope', function () {
            if (confirm('Are you sure you want to remove this scope?')) {
                $(this).closest('.postbox').remove();
            }
        });

        // Add Reason
        $container.on('click', '.cd-add-reason', function (e) {
            e.preventDefault();
            const $list = $(this).siblings('.cd-reasons-list');
            renderReasonRow($list, { slug: '', label: '' });
        });

        // Add Field
        $container.on('click', '.cd-add-field', function (e) {
            e.preventDefault();
            const $list = $(this).siblings('.cd-fields-list');
            renderFieldRow($list, { slug: '', label: '', type: 'text' });
        });

        // Remove Item (Row)
        $container.on('click', '.cd-remove-item', function () {
            $(this).closest('.cd-item-row').remove();
        });

        // --- functions ---

        function loadConfig() {
            $.post(claim_desk_admin.ajax_url, {
                action: 'claim_desk_get_config',
                nonce: claim_desk_admin.nonce
            }, function (res) {
                if (res.success) {
                    const scopes = res.data;
                    $container.empty();
                    $.each(scopes, function (key, scope) {
                        renderScope(scope);
                    });
                } else {
                    alert('Failed to load config');
                }
            });
        }

        function saveConfig() {
            $spinner.addClass('is-active');
            $saveBtn.prop('disabled', true);

            const scopes = [];
            $container.find('.cd-scope-card').each(function () {
                const $card = $(this);
                const scope = {
                    slug: $card.find('.cd-scope-slug-input').val(),
                    label: $card.find('.cd-scope-label-input').val(),
                    icon: $card.find('.cd-scope-icon-input').val(),
                    reasons: [],
                    fields: []
                };

                // Reasons
                $card.find('.cd-reasons-list .cd-item-row').each(function () {
                    scope.reasons.push({
                        label: $(this).find('.cd-reason-label').val(),
                        slug: $(this).find('.cd-reason-slug').val()
                    });
                });

                // Fields
                $card.find('.cd-fields-list .cd-item-row').each(function () {
                    scope.fields.push({
                        label: $(this).find('.cd-field-label').val(),
                        slug: $(this).find('.cd-field-slug').val(),
                        type: $(this).find('.cd-field-type').val(),
                        required: $(this).find('.cd-field-req').is(':checked')
                    });
                });

                scopes.push(scope);
            });

            $.post(claim_desk_admin.ajax_url, {
                action: 'claim_desk_save_config',
                nonce: claim_desk_admin.nonce,
                scopes: JSON.stringify(scopes)
            }, function (res) {
                $spinner.removeClass('is-active');
                $saveBtn.prop('disabled', false);
                if (res.success) {
                    alert('Configuration Saved!');
                } else {
                    alert('Error: ' + res.data);
                }
            });
        }

        function renderScope(scope) {
            let tmpl = $('#tmpl-cd-scope').html();
            tmpl = tmpl.replace(/{{label}}/g, scope.label)
                .replace(/{{slug}}/g, scope.slug)
                .replace(/{{icon}}/g, scope.icon);

            const $el = $(tmpl);

            // Populate Reasons
            const $reasonsList = $el.find('.cd-reasons-list');
            if (scope.reasons) {
                scope.reasons.forEach(r => renderReasonRow($reasonsList, r));
            }

            // Populate Fields
            const $fieldsList = $el.find('.cd-fields-list');
            if (scope.fields) {
                scope.fields.forEach(f => renderFieldRow($fieldsList, f));
            }

            $container.append($el);
        }

        function renderReasonRow($list, r) {
            const html = `
                <div class="cd-item-row">
                    <input type="text" class="cd-reason-label" placeholder="Label (e.g. Broken)" value="${r.label}">
                    <input type="text" class="cd-reason-slug" placeholder="Slug (e.g. broken)" value="${r.slug}">
                    <span class="dashicons dashicons-trash cd-remove-item"></span>
                </div>
            `;
            $list.append(html);
        }

        function renderFieldRow($list, f) {
            const html = `
                <div class="cd-item-row">
                    <input type="text" class="cd-field-label" placeholder="Label (e.g. Batch No)" value="${f.label}">
                    <input type="text" class="cd-field-slug" placeholder="Slug (e.g. batch_no)" value="${f.slug}">
                    <select class="cd-field-type">
                        <option value="text" ${f.type === 'text' ? 'selected' : ''}>Text</option>
                        <option value="textarea" ${f.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                        <option value="number" ${f.type === 'number' ? 'selected' : ''}>Number</option>
                    </select>
                    <label><input type="checkbox" class="cd-field-req" ${f.required ? 'checked' : ''}> Req?</label>
                    <span class="dashicons dashicons-trash cd-remove-item"></span>
                </div>
            `;
            $list.append(html);
        }

    }

})(jQuery);
