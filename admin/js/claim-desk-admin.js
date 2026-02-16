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
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        },

        close: function () {
            const modal = document.getElementById('cd-lightbox-modal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
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
        // Legacy Scopes Handlers
        // Legacy Scopes Handlers
        // Use document delegation to ensure it works even if DOM is tricky
        $(document).on('click', '#cd-add-scope', function (e) {
            e.preventDefault();
            const slug = prompt('Enter Scope Slug (e.g. quality, shipping):');
            if (slug) {
                renderScope($('#cd-legacy-scopes-container'), { slug: slug, label: slug, icon: 'admin-generic', reasons: [], fields: [] });
            }
        });

        $(document).on('click', '.cd-remove-scope', function () {
            if (confirm('Delete this scope?')) $(this).closest('.cd-scope-card').remove();
        });

        $(document).on('click', '.cd-add-reason', function (e) {
            e.preventDefault();
            const $list = $(this).siblings('.cd-reasons-list');
            const slug = prompt('Reason Value/Slug:');
            if (slug) {
                $list.append(`<div class="cd-reason-item" style="margin-bottom:5px; display:flex; gap:5px;">
                    <input type="text" class="reason-label regular-text" placeholder="Label" value="${slug}">
                    <input type="text" class="reason-slug" value="${slug}" readonly style="background:#eee; width:100px;">
                    <button type="button" class="button-link cd-remove-sub" style="color:red;">&times;</button>
                </div>`);
            }
        });

        $(document).on('click', '.cd-add-field', function (e) {
            e.preventDefault();
            const $list = $(this).siblings('.cd-fields-list');
            const slug = prompt('Field ID/Slug:');
            if (slug) {
                $list.append(`<div class="cd-field-item" style="margin-bottom:5px; display:flex; gap:5px;">
                    <input type="text" class="field-label regular-text" placeholder="Label" value="${slug}">
                    <input type="text" class="field-slug" value="${slug}" readonly style="background:#eee; width:100px;">
                    <select class="field-type">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="number">Number</option>
                        <option value="file">File</option>
                    </select>
                    <label><input type="checkbox" class="field-required"> Req</label>
                    <button type="button" class="button-link cd-remove-sub" style="color:red;">&times;</button>
                </div>`);
            }
        });

        $(document).on('click', '.cd-remove-sub', function () {
            $(this).parent().remove();
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

                    // Legacy Scopes
                    $('#cd-legacy-scopes-container').empty();
                    if (data.scopes) {
                        // scopes is object { slug: { ... } } or array? backend returns array from ConfigManager::get_scopes() if it was array. 
                        // But wait, update_option saves associative array [ slug => data ].
                        // So PHP returns associative array (object in JS).
                        $.each(data.scopes, function (slug, scope) {
                            renderScope($('#cd-legacy-scopes-container'), scope);
                        });
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

            // Gather Legacy Scopes
            const scopes = [];
            $('#cd-legacy-scopes-container .cd-scope-card').each(function () {
                const $card = $(this);
                const scope = {
                    slug: $card.find('.cd-scope-slug-input').val(),
                    label: $card.find('.cd-scope-label-input').val(),
                    icon: $card.find('.cd-scope-icon-input').val(),
                    reasons: [],
                    fields: []
                };

                $card.find('.cd-reason-item').each(function () {
                    scope.reasons.push({
                        label: $(this).find('.reason-label').val(),
                        slug: $(this).find('.reason-slug').val()
                    });
                });

                $card.find('.cd-field-item').each(function () {
                    scope.fields.push({
                        label: $(this).find('.field-label').val(),
                        slug: $(this).find('.field-slug').val(),
                        type: $(this).find('.field-type').val(),
                        required: $(this).find('.field-required').is(':checked')
                    });
                });

                scopes.push(scope);
            });

            $.post(claim_desk_admin.ajax_url, {
                action: 'claim_desk_save_config',
                nonce: claim_desk_admin.nonce,
                resolutions: resolutions,
                problems: JSON.stringify(problems),
                conditions: JSON.stringify(conditions),
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

        function renderRow($list, item) {
            let tmpl = $('#tmpl-cd-row').html();
            tmpl = tmpl.replace(/{{label}}/g, item.label)
                .replace(/{{value}}/g, item.value);
            $list.append(tmpl);
        }

        function renderScope($container, scope) {
            let tmpl = $('#tmpl-cd-scope').html();
            tmpl = tmpl.replace(/{{label}}/g, scope.label)
                .replace(/{{slug}}/g, scope.slug)
                .replace(/{{icon}}/g, scope.icon || 'admin-generic');

            const $el = $(tmpl);
            $container.append($el);

            // Render Reasons
            if (scope.reasons) {
                const $rList = $el.find('.cd-reasons-list');
                scope.reasons.forEach(r => {
                    $rList.append(`<div class="cd-reason-item" style="margin-bottom:5px; display:flex; gap:5px;">
                        <input type="text" class="reason-label regular-text" placeholder="Label" value="${r.label}">
                        <input type="text" class="reason-slug" value="${r.slug}" readonly style="background:#eee; width:100px;">
                        <button type="button" class="button-link cd-remove-sub" style="color:red;">&times;</button>
                    </div>`);
                });
            }

            // Render Fields
            if (scope.fields) {
                const $fList = $el.find('.cd-fields-list');
                scope.fields.forEach(f => {
                    $fList.append(`<div class="cd-field-item" style="margin-bottom:5px; display:flex; gap:5px;">
                        <input type="text" class="field-label regular-text" placeholder="Label" value="${f.label}">
                        <input type="text" class="field-slug" value="${f.slug}" readonly style="background:#eee; width:100px;">
                        <select class="field-type">
                            <option value="text" ${f.type == 'text' ? 'selected' : ''}>Text</option>
                            <option value="textarea" ${f.type == 'textarea' ? 'selected' : ''}>Textarea</option>
                            <option value="number" ${f.type == 'number' ? 'selected' : ''}>Number</option>
                            <option value="file" ${f.type == 'file' ? 'selected' : ''}>File</option>
                        </select>
                        <label><input type="checkbox" class="field-required" ${f.required ? 'checked' : ''}> Req</label>
                        <button type="button" class="button-link cd-remove-sub" style="color:red;">&times;</button>
                    </div>`);
                });
            }
        }

    }

})(jQuery);
