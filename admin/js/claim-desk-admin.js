(function ($) {
    'use strict';

    /**
     * Claim Desk Lightbox Manager
     */
    window.ClaimDeskLightbox = {
        attachments: [],
        currentIndex: 0,
        currentZoom: 100,
        minZoom: 50,
        maxZoom: 300,
        zoomStep: 25,

        init: function () {
            const dataElement = document.getElementById('cd-attachments-data');
            if (dataElement) {
                this.attachments = JSON.parse(dataElement.textContent);
            }

            // Update total count
            if (this.attachments.length > 0) {
                document.getElementById('cd-total-idx').textContent = this.attachments.length;
            }

            // Attach click handlers to gallery thumbnails using event delegation
            $(document).on('click', '.cd-gallery-thumb', (e) => {
                const idx = parseInt($(e.currentTarget).attr('data-idx'));
                if (!isNaN(idx)) {
                    this.open(idx);
                }
            });

            // Close button handler
            $(document).on('click', '.cd-lightbox-close', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.close();
            });

            // Zoom in button
            $(document).on('click', '.cd-zoom-in', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.zoomIn();
            });

            // Zoom out button
            $(document).on('click', '.cd-zoom-out', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.zoomOut();
            });

            // Reset zoom button
            $(document).on('click', '.cd-reset-zoom', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.resetZoom();
            });

            // Navigation prev button
            $(document).on('click', '.cd-nav-prev', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.prev();
            });

            // Navigation next button
            $(document).on('click', '.cd-nav-next', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.next();
            });

            // Keyboard shortcuts
            $(document).on('keydown', (e) => {
                const modal = document.getElementById('cd-lightbox-modal');
                if (!modal || !modal.classList.contains('active')) return;

                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.prev();
                }
                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.next();
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.close();
                }
                if (e.key === '+' || e.key === '=') {
                    e.preventDefault();
                    this.zoomIn();
                }
                if (e.key === '-') {
                    e.preventDefault();
                    this.zoomOut();
                }
            });

            // Close on outside click
            $(document).on('click', '#cd-lightbox-modal', (e) => {
                if (e.target.id === 'cd-lightbox-modal') {
                    this.close();
                }
            });
        },

        open: function (index) {
            if (!this.attachments || this.attachments.length === 0) return;

            this.currentIndex = index;
            this.currentZoom = 100;
            this.displayImage();

            const modal = document.getElementById('cd-lightbox-modal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        },

        close: function () {
            const modal = document.getElementById('cd-lightbox-modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        },

        displayImage: function () {
            const attachment = this.attachments[this.currentIndex];
            if (!attachment) return;

            const img = document.getElementById('cd-lightbox-image');
            const nameEl = document.getElementById('cd-image-name');
            const indexEl = document.getElementById('cd-current-idx');
            const sizeEl = document.getElementById('cd-image-size');
            const dateEl = document.getElementById('cd-image-date');

            img.src = attachment.url;
            nameEl.textContent = attachment.name;
            indexEl.textContent = this.currentIndex + 1;
            sizeEl.textContent = attachment.size + ' KB';
            dateEl.textContent = 'Uploaded: ' + attachment.date;

            this.updateZoom();
            this.updateNavButtons();
        },

        updateZoom: function () {
            const img = document.getElementById('cd-lightbox-image');
            const zoomLevel = document.getElementById('cd-zoom-level');

            img.style.transform = 'scale(' + (this.currentZoom / 100) + ')';
            zoomLevel.textContent = this.currentZoom + '%';
        },

        updateNavButtons: function () {
            const prevBtn = document.querySelector('.cd-nav-prev');
            const nextBtn = document.querySelector('.cd-nav-next');

            if (this.currentIndex === 0) {
                prevBtn.style.opacity = '0.3';
                prevBtn.style.cursor = 'not-allowed';
            } else {
                prevBtn.style.opacity = '1';
                prevBtn.style.cursor = 'pointer';
            }

            if (this.currentIndex === this.attachments.length - 1) {
                nextBtn.style.opacity = '0.3';
                nextBtn.style.cursor = 'not-allowed';
            } else {
                nextBtn.style.opacity = '1';
                nextBtn.style.cursor = 'pointer';
            }
        },

        prev: function () {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.displayImage();
            }
        },

        next: function () {
            if (this.currentIndex < this.attachments.length - 1) {
                this.currentIndex++;
                this.displayImage();
            }
        },

        zoomIn: function () {
            if (this.currentZoom < this.maxZoom) {
                this.currentZoom += this.zoomStep;
                if (this.currentZoom > this.maxZoom) {
                    this.currentZoom = this.maxZoom;
                }
                this.updateZoom();
            }
        },

        zoomOut: function () {
            if (this.currentZoom > this.minZoom) {
                this.currentZoom -= this.zoomStep;
                if (this.currentZoom < this.minZoom) {
                    this.currentZoom = this.minZoom;
                }
                this.updateZoom();
            }
        },

        resetZoom: function () {
            this.currentZoom = 100;
            this.updateZoom();
        }
    };

    /**
     * Claim Desk Admin Logic
     */
    $(document).ready(function () {
        // Initialize lightbox
        ClaimDeskLightbox.init();

        if ($('.claim-desk-config-wrapper').length) {
            initConfigPage();
        }
    });

    function initConfigPage() {
        const $saveBtn = $('#cd-save-config');
        const $spinner = $('.cd-header .spinner');

        // Tabs
        $('.claim-desk-config-wrapper .nav-tab-wrapper a').on('click', function (e) {
            e.preventDefault();
            $('.claim-desk-config-wrapper .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.cd-tab-content').hide();
            $($(this).attr('href')).show();
        });

        // Load Config
        loadConfig();

        // Save
        $saveBtn.on('click', saveConfig);
        $('#cd-claim-window-mode').on('change', toggleClaimWindowDaysField);
        $('#cd-reminder-delay').on('change', toggleReminderCustomDaysField);

        // Add Row Handlers
        $('#cd-add-problem').on('click', function (e) {
            e.preventDefault();
            renderRow($('#cd-problems-list'), { label: '', value: '' });
        });
        $('#cd-add-condition').on('click', function (e) {
            e.preventDefault();
            renderRow($('#cd-conditions-list'), { label: '', value: '' });
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

                    // Claim Window
                    if (data.claim_window) {
                        $('#cd-claim-window-mode').val(data.claim_window.mode || 'limited_days');
                        $('#cd-claim-window-days').val(parseInt(data.claim_window.days, 10) || 30);
                    } else {
                        $('#cd-claim-window-mode').val('limited_days');
                        $('#cd-claim-window-days').val(30);
                    }
                    toggleClaimWindowDaysField();

                    // Reminder Settings
                    if (data.reminder_settings) {
                        $('#cd-reminder-enabled').prop('checked', !!data.reminder_settings.enabled);
                        $('#cd-reminder-delay').val(data.reminder_settings.delay || '3');
                        $('#cd-reminder-custom-days').val(parseInt(data.reminder_settings.custom_days, 10) || 3);
                    } else {
                        $('#cd-reminder-enabled').prop('checked', false);
                        $('#cd-reminder-delay').val('3');
                        $('#cd-reminder-custom-days').val(3);
                    }
                    toggleReminderCustomDaysField();

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

            const claimWindow = {
                mode: $('#cd-claim-window-mode').val(),
                days: parseInt($('#cd-claim-window-days').val(), 10) || 1
            };

            const reminderSettings = {
                enabled: $('#cd-reminder-enabled').is(':checked'),
                delay: $('#cd-reminder-delay').val(),
                custom_days: parseInt($('#cd-reminder-custom-days').val(), 10) || 1
            };

            $.post(claim_desk_admin.ajax_url, {
                action: 'claim_desk_save_config',
                nonce: claim_desk_admin.nonce,
                resolutions: resolutions,
                problems: JSON.stringify(problems),
                conditions: JSON.stringify(conditions),
                claim_window: claimWindow,
                reminder_settings: reminderSettings
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

        function toggleClaimWindowDaysField() {
            const mode = $('#cd-claim-window-mode').val();
            if (mode === 'limited_days') {
                $('#cd-claim-window-days-wrap').show();
            } else {
                $('#cd-claim-window-days-wrap').hide();
            }
        }

        function toggleReminderCustomDaysField() {
            const delay = $('#cd-reminder-delay').val();
            if (delay === 'custom') {
                $('#cd-reminder-custom-days-wrap').show();
            } else {
                $('#cd-reminder-custom-days-wrap').hide();
            }
        }

    }

})(jQuery);
