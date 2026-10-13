/**
 * DEFE Custom Tables - General Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // 1-Click Copy Shortcode to Clipboard
        $(document).on('click', '.defe-copy-shortcode-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var shortcode = $btn.data('shortcode') || $btn.siblings('code').text();

            if (!shortcode) return;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    showCopiedFeedback($btn);
                }).catch(function() {
                    fallbackCopy(shortcode, $btn);
                });
            } else {
                fallbackCopy(shortcode, $btn);
            }
        });

        function fallbackCopy(text, $btn) {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            showCopiedFeedback($btn);
        }

        function showCopiedFeedback($btn) {
            var originalHtml = $btn.html();
            $btn.html('<span class="dashicons dashicons-yes"></span> ' + (defe_vars.strings.copied || 'Copied!'));
            $btn.css({'background': '#22C55E', 'color': '#FFFFFF', 'border-color': '#22C55E'});

            setTimeout(function() {
                $btn.html(originalHtml);
                $btn.removeAttr('style');
            }, 2000);
        }

        // Delete Table AJAX confirmation
        $(document).on('click', '.defe-btn-delete', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var tableId = $btn.data('id');

            if (!confirm(defe_vars.strings.confirm_delete)) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: defe_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'defe_delete_table',
                    table_id: tableId,
                    _ajax_nonce: defe_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#defe-row-' + tableId).fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message || 'Failed to delete table.');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Server error while deleting table.');
                    $btn.prop('disabled', false);
                }
            });
        });

        // Settings Provider Toggle
        $('#defe_setting_provider').on('change', function() {
            var val = $(this).val();
            if (val === 'browser_ai') {
                $('#defe-api-key-wrapper').slideUp();
                $('#defe-custom-endpoint-wrapper').slideUp();
            } else if (val === 'custom') {
                $('#defe-api-key-wrapper').slideDown();
                $('#defe-custom-endpoint-wrapper').slideDown();
                $('#defe-model-wrapper').hide();
            } else {
                $('#defe-api-key-wrapper').slideDown();
                $('#defe-custom-endpoint-wrapper').slideUp();
                $('#defe-model-wrapper').show();
            }
        });

        // Save Settings Form AJAX
        $('#defe-settings-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $('#defe-save-settings-btn');
            var $status = $('#defe-settings-status');

            $btn.prop('disabled', true);
            $status.text('Saving settings...').css('color', '#334155');

            var formData = $form.serializeArray();
            formData.push({name: 'action', value: 'defe_save_settings'});
            formData.push({name: '_ajax_nonce', value: defe_vars.nonce});

            $.ajax({
                url: defe_vars.ajax_url,
                type: 'POST',
                data: formData,
                success: function(res) {
                    $btn.prop('disabled', false);
                    if (res.success) {
                        $status.text(res.data.message || 'Settings saved!').css('color', '#16A34A');
                        setTimeout(function() { $status.text(''); }, 3000);
                    } else {
                        $status.text(res.data.message || 'Error saving settings.').css('color', '#DC2626');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    $status.text('Server communication failed.').css('color', '#DC2626');
                }
            });
        });

    });
})(jQuery);
