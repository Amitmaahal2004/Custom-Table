<?php
/**
 * Admin Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap defe-admin-wrap">
    <div class="defe-header-bar">
        <div class="defe-header-left">
            <h1 class="wp-heading-inline"><?php esc_html_e('DEFE Custom Tables — Settings', 'defe-custom-tables'); ?></h1>
        </div>
        <div class="defe-header-right">
            <a href="<?php echo esc_url(admin_url('admin.php?page=defe-custom-tables')); ?>" class="button button-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e('Back to All Tables', 'defe-custom-tables'); ?>
            </a>
        </div>
    </div>

    <div class="defe-settings-container">
        <form id="defe-settings-form" method="post" action="">
            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('defe_admin_nonce')); ?>">

            <!-- OCR Configuration Card -->
            <div class="defe-card">
                <div class="defe-card-header">
                    <h2><span class="dashicons dashicons-camera"></span> <?php esc_html_e('Image Import & OCR Engine Settings', 'defe-custom-tables'); ?></h2>
                </div>
                <div class="defe-card-body">
                    
                    <div class="defe-form-row">
                        <div class="defe-form-col defe-col-6">
                            <label for="defe_setting_provider"><strong><?php esc_html_e('OCR / Table Detection Provider', 'defe-custom-tables'); ?></strong></label>
                            <select id="defe_setting_provider" name="provider" class="widefat">
                                <option value="browser_ai" <?php selected($ocr_settings['provider'], 'browser_ai'); ?>><?php esc_html_e('Browser Built-in Engine (Free, Zero Configuration)', 'defe-custom-tables'); ?></option>
                                <option value="gemini" <?php selected($ocr_settings['provider'], 'gemini'); ?>><?php esc_html_e('Google Gemini Vision API (Recommended for complex tables)', 'defe-custom-tables'); ?></option>
                                <option value="openai" <?php selected($ocr_settings['provider'], 'openai'); ?>><?php esc_html_e('OpenAI GPT-4o Vision API', 'defe-custom-tables'); ?></option>
                                <option value="custom" <?php selected($ocr_settings['provider'], 'custom'); ?>><?php esc_html_e('Custom OCR Webhook / API Endpoint', 'defe-custom-tables'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Select which engine to use when converting screenshot images to editable tables.', 'defe-custom-tables'); ?></p>
                        </div>
                        <div class="defe-form-col defe-col-6">
                            <label for="defe_setting_max_size"><strong><?php esc_html_e('Maximum Image Upload Size (MB)', 'defe-custom-tables'); ?></strong></label>
                            <input type="number" id="defe_setting_max_size" name="max_image_size" class="widefat" min="1" max="50" value="<?php echo esc_attr($ocr_settings['max_image_size']); ?>">
                            <p class="description"><?php esc_html_e('Prevents server memory exhaustion during image uploads.', 'defe-custom-tables'); ?></p>
                        </div>
                    </div>

                    <!-- API Key row (Visible for Gemini / OpenAI / Custom) -->
                    <div id="defe-api-key-wrapper" class="defe-form-row" style="<?php echo ($ocr_settings['provider'] === 'browser_ai') ? 'display:none;' : ''; ?>">
                        <div class="defe-form-col defe-col-8">
                            <label for="defe_setting_api_key"><strong><?php esc_html_e('API Key', 'defe-custom-tables'); ?></strong></label>
                            <input type="password" id="defe_setting_api_key" name="api_key" class="widefat" value="<?php echo esc_attr($ocr_settings['api_key']); ?>" placeholder="<?php esc_attr_e('Enter API key...', 'defe-custom-tables'); ?>">
                            <p class="description"><?php esc_html_e('Your API key is securely stored in your WordPress database and is NEVER exposed to frontend users or public scripts.', 'defe-custom-tables'); ?></p>
                        </div>
                        <div class="defe-form-col defe-col-4" id="defe-model-wrapper">
                            <label for="defe_setting_model"><strong><?php esc_html_e('Vision Model', 'defe-custom-tables'); ?></strong></label>
                            <input type="text" id="defe_setting_model" name="model" class="widefat" value="<?php echo esc_attr($ocr_settings['model']); ?>" placeholder="gemini-1.5-flash">
                        </div>
                    </div>

                    <!-- Custom Endpoint field -->
                    <div id="defe-custom-endpoint-wrapper" class="defe-form-row" style="<?php echo ($ocr_settings['provider'] !== 'custom') ? 'display:none;' : ''; ?>">
                        <div class="defe-form-col defe-col-12">
                            <label for="defe_setting_api_endpoint"><strong><?php esc_html_e('Custom Endpoint URL', 'defe-custom-tables'); ?></strong></label>
                            <input type="url" id="defe_setting_api_endpoint" name="api_endpoint" class="widefat" value="<?php echo esc_attr($ocr_settings['api_endpoint']); ?>" placeholder="https://api.example.com/ocr-table">
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="defe-form-row">
                        <div class="defe-form-col defe-col-12">
                            <label class="defe-setting-checkbox">
                                <input type="checkbox" name="enable_preprocess" value="1" <?php checked(!empty($ocr_settings['enable_preprocess'])); ?>>
                                <strong><?php esc_html_e('Enable automatic image enhancement', 'defe-custom-tables'); ?></strong>
                                <span class="description">(<?php esc_html_e('Applies contrast and sharpness optimization before OCR extraction', 'defe-custom-tables'); ?>)</span>
                            </label>
                        </div>
                        <div class="defe-form-col defe-col-12">
                            <label class="defe-setting-checkbox">
                                <input type="checkbox" name="detect_total_row" value="1" <?php checked(!empty($ocr_settings['detect_total_row'])); ?>>
                                <strong><?php esc_html_e('Auto-detect Total / Footer row', 'defe-custom-tables'); ?></strong>
                                <span class="description">(<?php esc_html_e('Automatically places summary/total lines in the table footer', 'defe-custom-tables'); ?>)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Privacy & Security Notice -->
                    <div class="notice notice-info inline defe-privacy-box">
                        <p>
                            <span class="dashicons dashicons-shield"></span>
                            <strong><?php esc_html_e('Image Privacy Notice', 'defe-custom-tables'); ?>:</strong>
                            <?php esc_html_e('When using Browser Built-in mode, images are processed locally. When using Gemini or OpenAI, image data is transmitted securely over HTTPS directly to the chosen API provider to extract table columns and rows.', 'defe-custom-tables'); ?>
                        </p>
                    </div>

                </div>
                <div class="defe-card-footer">
                    <button type="submit" class="button button-primary button-large" id="defe-save-settings-btn">
                        <span class="dashicons dashicons-saved"></span> <?php esc_html_e('Save Settings', 'defe-custom-tables'); ?>
                    </button>
                    <span id="defe-settings-status" style="margin-left: 10px; font-weight: 600;"></span>
                </div>
            </div>

            <!-- Shortcode & Elementor Quick Guide -->
            <div class="defe-card">
                <div class="defe-card-header">
                    <h2><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e('Usage & Elementor Integration', 'defe-custom-tables'); ?></h2>
                </div>
                <div class="defe-card-body">
                    <p><?php esc_html_e('Each table you create has a unique shortcode. You can place this shortcode in standard WordPress pages, posts, or widgets.', 'defe-custom-tables'); ?></p>
                    
                    <div class="defe-guide-grid">
                        <div class="defe-guide-step">
                            <h4><?php esc_html_e('1. Elementor Free Usage', 'defe-custom-tables'); ?></h4>
                            <p><?php esc_html_e('In Elementor editor, drag the Shortcode widget into any section and enter [defe_table id="123"]. Save and publish.', 'defe-custom-tables'); ?></p>
                        </div>
                        <div class="defe-guide-step">
                            <h4><?php esc_html_e('2. Multiple Independent Tables', 'defe-custom-tables'); ?></h4>
                            <p><?php esc_html_e('You can place multiple different shortcodes on the same page (e.g. [defe_table id="101"] and [defe_table id="102"]). Each keeps its own styles and colors without conflict.', 'defe-custom-tables'); ?></p>
                        </div>
                        <div class="defe-guide-step">
                            <h4><?php esc_html_e('3. Mobile Responsive', 'defe-custom-tables'); ?></h4>
                            <p><?php esc_html_e('All tables automatically feature smooth horizontal scrolling on mobile and tablet screens without overflowing your website layout.', 'defe-custom-tables'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>
