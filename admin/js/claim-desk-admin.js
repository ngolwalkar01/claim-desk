(function ($) {
    'use strict';

    /**
     * Claim Desk Admin Logic
     */
    $(document).ready(function () {
        if ($('.claim-desk-config-wrapper').length) {
            initConfigPage();
        }
    });

    function initConfigPage() {
        const $saveBtn = $('#cd-save-config');
        const $spinner = $('.cd-header .spinner');

        // Tabs
        $('.nav-tab-wrapper a').on('click', function (e) {
            e.preventDefault();
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.cd-tab-content').hide();
            $($(this).attr('href')).show();
        });

        // Load Config
        loadConfig();

        // Save
        $saveBtn.on('click', saveConfig);

        // Add Row Handlers
        $('#cd-add-problem').on('click', function (e) {
            e.preventDefault();
            renderRow($('#cd-problems-list'), { label: '', value: '' });
        });
        $('#cd-add-condition').on('click', function (e) {
            e.preventDefault();
            renderRow($('#cd-conditions-list'), { label: '', value: '' });
        });

        // Remove Row
        $(document).on('click', '.cd-remove-row', function () {
            if (confirm('Remove this item?')) {
                $(this).closest('tr').remove();
            }
        });

        // --- functions ---

        function loadConfig() {
            $.post(claim_desk_admin.ajax_url, {
                action: 'claim_desk_get_config',
                nonce: claim_desk_admin.nonce
            }, function (res) {
                if (res.success) {
                    const data = res.data;

                    // Resolutions
                    if (data.resolutions) {
                        $('#res-return').prop('checked', data.resolutions.return);
                        $('#res-exchange').prop('checked', data.resolutions.exchange);
                        $('#res-coupon').prop('checked', data.resolutions.coupon);
                    }

                    // Problems
                    $('#cd-problems-list').empty();
                    if (data.problems) {
                        data.problems.forEach(p => renderRow($('#cd-problems-list'), p));
                    }

                    // Conditions
                    $('#cd-conditions-list').empty();
                    if (data.conditions) {
                        data.conditions.forEach(c => renderRow($('#cd-conditions-list'), c));
                    }

                } else {
                    alert('Failed to load config');
                }
            });
        }

        function saveConfig() {
            $spinner.addClass('is-active');
            $saveBtn.prop('disabled', true);

            // Gather Data
            const resolutions = {
                return: $('#res-return').is(':checked'),
                exchange: $('#res-exchange').is(':checked'),
                coupon: $('#res-coupon').is(':checked')
            };

            const problems = [];
            $('#cd-problems-list tr').each(function () {
                problems.push({
                    label: $(this).find('.cd-item-label').val(),
                    value: $(this).find('.cd-item-value').val()
                });
            });

            const conditions = [];
            $('#cd-conditions-list tr').each(function () {
                conditions.push({
                    label: $(this).find('.cd-item-label').val(),
                    value: $(this).find('.cd-item-value').val()
                });
            });

            $.post(claim_desk_admin.ajax_url, {
                action: 'claim_desk_save_config',
                nonce: claim_desk_admin.nonce,
                resolutions: resolutions,
                problems: JSON.stringify(problems),
                conditions: JSON.stringify(conditions)
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

        function renderRow($list, item) {
            let tmpl = $('#tmpl-cd-row').html();
            tmpl = tmpl.replace(/{{label}}/g, item.label)
                .replace(/{{value}}/g, item.value);
            $list.append(tmpl);
        }

    }

})(jQuery);
