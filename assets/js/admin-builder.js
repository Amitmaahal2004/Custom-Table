/**
 * DEFE Custom Tables - Interactive Table Builder Engine
 */

window.DEFE_Builder = (function($) {
    'use strict';

    var state = {
        id: 0,
        name: 'New Custom Table',
        status: 'publish',
        columns: [],
        rows: [],
        total_row: {
            enabled: 0,
            cells: []
        },
        settings: {}
    };

    function init() {
        loadInitialData();
        initColorPickers();
        bindEvents();
        renderGrid();
        renderExtraContent();
        updateGridCounts();
    }

    function loadInitialData() {
        var rawJson = $('#defe_custom_table_raw_data').val();
        if (rawJson) {
            try {
                var parsed = JSON.parse(rawJson);
                state = $.extend(true, {}, state, parsed);
            } catch (e) {
                console.error('Failed to parse initial table data JSON', e);
            }
        }

        // Ensure at least 3 columns and 3 rows if empty
        if (!state.columns || state.columns.length === 0) {
            state.columns = [
                { id: 'col_1', name: 'Header 1', width: 'auto', align: 'left', total_calc: 'none' },
                { id: 'col_2', name: 'Header 2', width: 'auto', align: 'left', total_calc: 'none' },
                { id: 'col_3', name: 'Header 3', width: 'auto', align: 'left', total_calc: 'none' }
            ];
        }

        if (!state.rows || state.rows.length === 0) {
            state.rows = [
                { id: 'row_1', cells: ['', '', ''] },
                { id: 'row_2', cells: ['', '', ''] },
                { id: 'row_3', cells: ['', '', ''] }
            ];
        }

        if (!state.total_row || !state.total_row.cells) {
            state.total_row = {
                enabled: 0,
                cells: ['Total', '', '']
            };
        }

        if (!state.settings) {
            state.settings = {};
        }
    }

    function initColorPickers() {
        $('.defe-color-picker').each(function() {
            var $input = $(this);
            $input.wpColorPicker({
                change: function(event, ui) {
                    syncSettingsFromInputs();
                    updateHeaderColorLive(ui.color.toString());
                },
                clear: function() {
                    syncSettingsFromInputs();
                }
            });
        });
    }

    function updateHeaderColorLive(color) {
        if ($('#defe_header_bg_color').is(':focus') || color) {
            $('#defe-interactive-table thead th:not(.defe-grid-corner)').css('background-color', color);
        }
    }

    function bindEvents() {
        // Dismiss Choice banner
        $('.defe-choice-dismiss-btn').on('click', function() {
            $('#defe-choice-banner').slideUp();
        });

        // Add Column
        $('#defe-add-column-btn, #defe-add-column-btn-bottom').on('click', function() {
            addColumn();
        });

        // Add Row
        $('#defe-add-row-btn, #defe-add-row-btn-bottom').on('click', function() {
            addRow();
        });

        // Toggle Total Row checkbox
        $('#defe_enable_total_row').on('change', function() {
            var isChecked = $(this).is(':checked') ? 1 : 0;
            state.settings.enable_total_row = isChecked;
            state.total_row.enabled = isChecked;
            if (isChecked) {
                $('#defe-total-row-options').slideDown();
            } else {
                $('#defe-total-row-options').slideUp();
            }
            renderGrid();
        });

        // Toggle Zebra Rows
        $('#defe_enable_zebra').on('change', function() {
            if ($(this).is(':checked')) {
                $('#defe-zebra-color-row').slideDown();
            } else {
                $('#defe-zebra-color-row').slideUp();
            }
            syncSettingsFromInputs();
        });

        // Settings input changes
        $('.defe-builder-sidebar input, .defe-builder-sidebar select, #defe_show_title, #defe_show_description, #defe_custom_table_name, #defe_custom_table_description').on('change input', function() {
            syncSettingsFromInputs();
        });

        // Extra Content lines
        $('#defe-add-extra-line-btn').on('click', function() {
            addExtraContentLine();
        });

        $(document).on('click', '.defe-repeater-del', function() {
            $(this).closest('.defe-repeater-item').remove();
            syncExtraContentFromDOM();
        });

        $(document).on('input change', '.defe-repeater-item textarea, .defe-repeater-item select', function() {
            syncExtraContentFromDOM();
        });

        // Live Preview Modal Toggle
        $('#defe-toggle-preview-btn').on('click', function() {
            openLivePreview();
        });

        $('#defe-preview-modal .defe-modal-close-btn, #defe-preview-modal .defe-modal-backdrop').on('click', function() {
            $('#defe-preview-modal').fadeOut(200);
        });

        $('.defe-preview-device-toggle button').on('click', function() {
            $('.defe-preview-device-toggle button').removeClass('active');
            $(this).addClass('active');
            var device = $(this).data('device');
            $('#defe-preview-frame').removeClass('desktop tablet mobile').addClass(device);
        });

        // Save Table
        $('.defe-save-table-btn').on('click', function() {
            saveTable();
        });
    }

    function syncSettingsFromInputs() {
        state.name = $('#defe_custom_table_name').val() || 'Untitled Table';
        state.status = $('#defe_custom_table_status').val() || 'publish';

        state.settings = {
            show_title: $('#defe_show_title').is(':checked') ? 1 : 0,
            show_description: $('#defe_show_description').is(':checked') ? 1 : 0,
            description: $('#defe_custom_table_description').val(),
            header_bg_color: $('#defe_header_bg_color').val() || '#009C3B',
            header_text_color: $('#defe_header_text_color').val() || '#FFFFFF',
            header_font_size: $('#defe_header_font_size').val() || '15px',
            header_font_weight: $('#defe_header_font_weight').val() || '600',
            body_text_color: $('#defe_body_text_color').val() || '#333333',
            body_bg_color: $('#defe_body_bg_color').val() || '#FFFFFF',
            enable_zebra: $('#defe_enable_zebra').is(':checked') ? 1 : 0,
            zebra_bg_color: $('#defe_zebra_bg_color').val() || '#F9FAFB',
            border_color: $('#defe_border_color').val() || '#E5E7EB',
            border_width: $('#defe_border_width').val() || '1px',
            border_style: $('#defe_border_style').val() || 'solid',
            border_radius: $('#defe_border_radius').val() || '8px',
            cell_padding: $('#defe_cell_padding').val() || '12px',
            font_size: $('#defe_font_size').val() || '14px',
            vertical_alignment: $('#defe_vertical_alignment').val() || 'middle',
            default_alignment: $('#defe_default_alignment').val() || 'left',
            enable_total_row: $('#defe_enable_total_row').is(':checked') ? 1 : 0,
            total_mode: $('#defe_total_mode').val() || 'manual',
            total_bg_color: $('#defe_total_bg_color').val() || '#F0FDF4',
            total_text_color: $('#defe_total_text_color').val() || '#009C3B',
            total_font_weight: '700',
            extra_content: state.settings.extra_content || []
        };
    }

    function renderGrid() {
        var $table = $('#defe-interactive-table');
        $table.empty();

        var headerBg = $('#defe_header_bg_color').val() || '#009C3B';
        var headerColor = $('#defe_header_text_color').val() || '#FFFFFF';

        // 1. Header (THEAD)
        var thead = '<thead><tr>';
        thead += '<th class="defe-grid-corner">#</th>';

        $.each(state.columns, function(cIdx, col) {
            var colName = col.name || ('Column ' + (cIdx + 1));
            var colWidth = col.width || 'auto';
            var colAlign = col.align || 'left';
            var totalCalc = col.total_calc || 'none';

            thead += '<th class="defe-builder-th" data-col-idx="' + cIdx + '" style="background-color: ' + headerBg + '; color: ' + headerColor + ';">';
            thead += '<div class="defe-col-header-control">';
            thead += '<input type="text" class="defe-col-input" data-col-idx="' + cIdx + '" value="' + escapeHtml(colName) + '" placeholder="Column Name">';
            thead += '<div class="defe-col-settings-bar">';
            
            // Column alignment toggle
            thead += '<select class="defe-col-align-select" data-col-idx="' + cIdx + '" title="Alignment">';
            thead += '<option value="left"' + (colAlign === 'left' ? ' selected' : '') + '>Left</option>';
            thead += '<option value="center"' + (colAlign === 'center' ? ' selected' : '') + '>Center</option>';
            thead += '<option value="right"' + (colAlign === 'right' ? ' selected' : '') + '>Right</option>';
            thead += '</select>';

            // Column width input
            thead += '<input type="text" class="defe-col-width-input" data-col-idx="' + cIdx + '" value="' + escapeHtml(colWidth) + '" title="Column Width (e.g. 20%, 150px, auto)" style="width: 55px;">';

            // Column Auto Calc
            thead += '<select class="defe-col-calc-select" data-col-idx="' + cIdx + '" title="Footer Formula">';
            thead += '<option value="none"' + (totalCalc === 'none' ? ' selected' : '') + '>None</option>';
            thead += '<option value="sum"' + (totalCalc === 'sum' ? ' selected' : '') + '>SUM</option>';
            thead += '<option value="count"' + (totalCalc === 'count' ? ' selected' : '') + '>COUNT</option>';
            thead += '<option value="avg"' + (totalCalc === 'avg' ? ' selected' : '') + '>AVG</option>';
            thead += '</select>';

            // Delete Column button
            thead += '<button type="button" class="defe-col-delete-btn" data-col-idx="' + cIdx + '" title="Delete Column">&times;</button>';
            thead += '</div></div></th>';
        });

        thead += '</tr></thead>';
        $table.append(thead);

        // 2. Body (TBODY)
        var tbody = '<tbody>';
        $.each(state.rows, function(rIdx, row) {
            tbody += '<tr class="defe-builder-tr" data-row-idx="' + rIdx + '">';
            
            // Row handle & action buttons
            tbody += '<td class="defe-row-action-col">';
            tbody += '<div class="defe-row-btn-group">';
            tbody += '<span class="defe-row-num">' + (rIdx + 1) + '</span>';
            tbody += '<button type="button" class="defe-row-btn defe-row-dup-btn" data-row-idx="' + rIdx + '" title="Duplicate Row"><span class="dashicons dashicons-admin-page"></span></button>';
            tbody += '<button type="button" class="defe-row-btn defe-row-delete-btn" data-row-idx="' + rIdx + '" title="Delete Row"><span class="dashicons dashicons-trash"></span></button>';
            tbody += '</div></td>';

            // Row cells
            $.each(state.columns, function(cIdx) {
                var cellVal = (row.cells && typeof row.cells[cIdx] !== 'undefined') ? row.cells[cIdx] : '';
                tbody += '<td class="defe-builder-td">';
                tbody += '<textarea class="defe-cell-textarea" data-row-idx="' + rIdx + '" data-col-idx="' + cIdx + '" placeholder="Cell...">' + escapeHtml(cellVal) + '</textarea>';
                tbody += '</td>';
            });

            tbody += '</tr>';
        });
        tbody += '</tbody>';
        $table.append(tbody);

        // 3. Footer / Total Row (TFOOT)
        if (state.settings.enable_total_row) {
            var tfoot = '<tfoot><tr class="defe-builder-total-tr">';
            tfoot += '<td class="defe-row-action-col" style="font-weight: bold; color: #009C3B;">' + (defe_vars.strings.total || 'Total') + '</td>';

            $.each(state.columns, function(cIdx, col) {
                var totalVal = (state.total_row.cells && typeof state.total_row.cells[cIdx] !== 'undefined') ? state.total_row.cells[cIdx] : '';
                var isAuto = state.settings.total_mode === 'auto' && col.total_calc && col.total_calc !== 'none';
                
                tfoot += '<td class="defe-builder-total-td">';
                if (isAuto) {
                    tfoot += '<input type="text" class="defe-total-cell-input" disabled value="[' + col.total_calc.toUpperCase() + ' Auto]" style="background: #E2E8F0; opacity: 0.8;">';
                } else {
                    tfoot += '<input type="text" class="defe-total-cell-input" data-col-idx="' + cIdx + '" value="' + escapeHtml(totalVal) + '" placeholder="Total / Summary">';
                }
                tfoot += '</td>';
            });

            tfoot += '</tr></tfoot>';
            $table.append(tfoot);
        }

        bindGridDynamicEvents();
        updateGridCounts();
    }

    function bindGridDynamicEvents() {
        var $table = $('#defe-interactive-table');

        // Column Name change
        $table.find('.defe-col-input').off('input').on('input', function() {
            var cIdx = parseInt($(this).data('col-idx'), 10);
            state.columns[cIdx].name = $(this).val();
        });

        // Column Alignment change
        $table.find('.defe-col-align-select').off('change').on('change', function() {
            var cIdx = parseInt($(this).data('col-idx'), 10);
            state.columns[cIdx].align = $(this).val();
        });

        // Column Width change
        $table.find('.defe-col-width-input').off('change').on('change', function() {
            var cIdx = parseInt($(this).data('col-idx'), 10);
            state.columns[cIdx].width = $(this).val();
        });

        // Column Calc change
        $table.find('.defe-col-calc-select').off('change').on('change', function() {
            var cIdx = parseInt($(this).data('col-idx'), 10);
            state.columns[cIdx].total_calc = $(this).val();
            renderGrid();
        });

        // Column Delete
        $table.find('.defe-col-delete-btn').off('click').on('click', function() {
            var cIdx = parseInt($(this).data('col-idx'), 10);
            if (state.columns.length <= 1) {
                alert('Table must have at least 1 column.');
                return;
            }
            if (confirm(defe_vars.strings.confirm_delete_col)) {
                deleteColumn(cIdx);
            }
        });

        // Cell content change
        $table.find('.defe-cell-textarea').off('input').on('input', function() {
            var rIdx = parseInt($(this).data('row-idx'), 10);
            var cIdx = parseInt($(this).data('col-idx'), 10);
            if (!state.rows[rIdx].cells) {
                state.rows[rIdx].cells = [];
            }
            state.rows[rIdx].cells[cIdx] = $(this).val();
        });

        // Row Duplicate
        $table.find('.defe-row-dup-btn').off('click').on('click', function() {
            var rIdx = parseInt($(this).data('row-idx'), 10);
            duplicateRow(rIdx);
        });

        // Row Delete
        $table.find('.defe-row-delete-btn').off('click').on('click', function() {
            var rIdx = parseInt($(this).data('row-idx'), 10);
            if (state.rows.length <= 1) {
                alert('Table must have at least 1 row.');
                return;
            }
            if (confirm(defe_vars.strings.confirm_delete_row)) {
                deleteRow(rIdx);
            }
        });

        // Total cell change
        $table.find('.defe-total-cell-input').off('input').on('input', function() {
            var cIdx = parseInt($(this).data('col-idx'), 10);
            if (!state.total_row.cells) {
                state.total_row.cells = [];
            }
            state.total_row.cells[cIdx] = $(this).val();
        });
    }

    function addColumn() {
        var newColIdx = state.columns.length + 1;
        state.columns.push({
            id: 'col_' + newColIdx,
            name: 'Header ' + newColIdx,
            width: 'auto',
            align: state.settings.default_alignment || 'left',
            total_calc: 'none'
        });

        // Pad all rows with empty cell
        $.each(state.rows, function(rIdx, row) {
            if (!row.cells) row.cells = [];
            row.cells.push('');
        });

        // Pad total row
        if (state.total_row && state.total_row.cells) {
            state.total_row.cells.push('');
        }

        renderGrid();
    }

    function deleteColumn(index) {
        state.columns.splice(index, 1);

        $.each(state.rows, function(rIdx, row) {
            if (row.cells && row.cells.length > index) {
                row.cells.splice(index, 1);
            }
        });

        if (state.total_row && state.total_row.cells && state.total_row.cells.length > index) {
            state.total_row.cells.splice(index, 1);
        }

        renderGrid();
    }

    function addRow() {
        var emptyCells = [];
        for (var i = 0; i < state.columns.length; i++) {
            emptyCells.push('');
        }
        state.rows.push({
            id: 'row_' + (state.rows.length + 1),
            cells: emptyCells
        });
        renderGrid();
    }

    function duplicateRow(index) {
        var sourceRow = state.rows[index];
        var clonedCells = sourceRow.cells ? sourceRow.cells.slice() : [];
        state.rows.splice(index + 1, 0, {
            id: 'row_' + (state.rows.length + 1),
            cells: clonedCells
        });
        renderGrid();
    }

    function deleteRow(index) {
        state.rows.splice(index, 1);
        renderGrid();
    }

    function updateGridCounts() {
        $('#defe-col-count-badge').text(state.columns.length);
        $('#defe-row-count-badge').text(state.rows.length);
    }

    function renderExtraContent() {
        var $repeater = $('#defe-extra-content-repeater');
        $repeater.empty();

        var items = state.settings.extra_content || [];
        if (items.length === 0) {
            return;
        }

        $.each(items, function(idx, item) {
            var html = '<div class="defe-repeater-item">';
            html += '<textarea class="defe-extra-text" rows="2" placeholder="e.g. Footnote or reference notes...">' + escapeHtml(item.content || '') + '</textarea>';
            html += '<select class="defe-extra-pos">';
            html += '<option value="before"' + (item.position === 'before' ? ' selected' : '') + '>Before Table</option>';
            html += '<option value="after"' + (item.position === 'after' ? ' selected' : '') + '>After Table</option>';
            html += '</select>';
            html += '<button type="button" class="defe-repeater-del" title="Remove">&times;</button>';
            html += '</div>';
            $repeater.append(html);
        });
    }

    function addExtraContentLine() {
        if (!state.settings.extra_content) {
            state.settings.extra_content = [];
        }
        state.settings.extra_content.push({ content: '', position: 'after' });
        renderExtraContent();
    }

    function syncExtraContentFromDOM() {
        var list = [];
        $('#defe-extra-content-repeater .defe-repeater-item').each(function() {
            var text = $(this).find('.defe-extra-text').val();
            var pos = $(this).find('.defe-extra-pos').val();
            if (text) {
                list.push({ content: text, position: pos });
            }
        });
        state.settings.extra_content = list;
    }

    function openLivePreview() {
        syncSettingsFromInputs();
        syncExtraContentFromDOM();

        var $modal = $('#defe-preview-modal');
        var $target = $('#defe-preview-content-target');
        $target.html('<div style="text-align:center; padding:30px;"><span class="spinner is-active" style="float:none;"></span> Generating preview...</div>');
        $modal.fadeIn(200);

        var payload = {
            id: state.id || 1,
            name: state.name,
            status: state.status,
            columns: state.columns,
            rows: state.rows,
            total_row: state.total_row,
            settings: state.settings
        };

        $.ajax({
            url: defe_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'defe_preview_table',
                table_data: JSON.stringify(payload),
                _ajax_nonce: defe_vars.nonce
            },
            success: function(res) {
                if (res.success) {
                    $target.html(res.data.html);
                } else {
                    $target.html('<div class="notice notice-error"><p>' + (res.data.message || 'Error loading preview') + '</p></div>');
                }
            },
            error: function() {
                $target.html('<div class="notice notice-error"><p>Could not connect to preview renderer.</p></div>');
            }
        });
    }

    function saveTable() {
        syncSettingsFromInputs();
        syncExtraContentFromDOM();

        var $btn = $('.defe-save-table-btn');
        $btn.prop('disabled', true).text(defe_vars.strings.saving || 'Saving Table...');

        var payload = {
            id: state.id || 0,
            name: state.name,
            status: state.status,
            columns: state.columns,
            rows: state.rows,
            total_row: state.total_row,
            settings: state.settings
        };

        $.ajax({
            url: defe_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'defe_save_table',
                table_data: JSON.stringify(payload),
                _ajax_nonce: defe_vars.nonce
            },
            success: function(res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Table');

                if (res.success) {
                    if (res.data.redirect_url && (!state.id || state.id === 0)) {
                        window.location.href = res.data.redirect_url;
                    } else {
                        alert(defe_vars.strings.saved || 'Table saved successfully!');
                    }
                } else {
                    alert(res.data.message || defe_vars.strings.save_error);
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Table');
                alert(defe_vars.strings.save_error || 'Error saving table.');
            }
        });
    }

    // Public method to accept OCR extracted structured data
    function importData(extractedData) {
        if (!extractedData) return;

        if (extractedData.name) {
            state.name = extractedData.name;
            $('#defe_custom_table_name').val(extractedData.name);
        }

        if (extractedData.columns && extractedData.columns.length > 0) {
            state.columns = extractedData.columns;
        }

        if (extractedData.rows && extractedData.rows.length > 0) {
            state.rows = extractedData.rows;
        }

        if (extractedData.total_row) {
            state.total_row = extractedData.total_row;
            state.settings.enable_total_row = extractedData.total_row.enabled ? 1 : 0;
            $('#defe_enable_total_row').prop('checked', !!extractedData.total_row.enabled);
            if (extractedData.total_row.enabled) {
                $('#defe-total-row-options').show();
            }
        }

        if (extractedData.header_bg_color) {
            state.settings.header_bg_color = extractedData.header_bg_color;
            $('#defe_header_bg_color').val(extractedData.header_bg_color).trigger('change');
        }

        renderGrid();
    }

    function escapeHtml(str) {
        if (str === null || typeof str === 'undefined') return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    return {
        init: init,
        importData: importData
    };

})(jQuery);

jQuery(document).ready(function() {
    DEFE_Builder.init();
});
