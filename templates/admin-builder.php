<?php
/**
 * Admin Table Builder & Image Import Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_new = empty($table['id']);
$table_json = wp_json_encode($table);
$shortcode_str = !$is_new ? sprintf('[defe_table id="%d"]', $table['id']) : '';
?>

<div class="wrap defe-admin-wrap defe-builder-wrap">
    <!-- Top Action Header -->
    <div class="defe-builder-header">
        <div class="defe-builder-header-left">
            <a href="<?php echo esc_url(admin_url('admin.php?page=defe-custom-tables')); ?>" class="defe-back-link">
                <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e('All Tables', 'defe-custom-tables'); ?>
            </a>
            <h1 class="defe-builder-title">
                <?php echo $is_new ? esc_html__('Add New Table', 'defe-custom-tables') : esc_html__('Edit Table', 'defe-custom-tables'); ?>
            </h1>
        </div>

        <div class="defe-builder-header-right">
            <?php if (!$is_new) : ?>
                <div class="defe-shortcode-pill defe-header-shortcode">
                    <code><?php echo esc_html($shortcode_str); ?></code>
                    <button type="button" class="button button-small defe-copy-shortcode-btn" data-shortcode="<?php echo esc_attr($shortcode_str); ?>">
                        <span class="dashicons dashicons-admin-page"></span> <?php esc_html_e('Copy', 'defe-custom-tables'); ?>
                    </button>
                </div>
            <?php endif; ?>

            <button type="button" class="button button-secondary" id="defe-open-import-modal-btn">
                <span class="dashicons dashicons-format-image"></span> <?php esc_html_e('Import From Image', 'defe-custom-tables'); ?>
            </button>

            <button type="button" class="button button-secondary" id="defe-toggle-preview-btn">
                <span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Live Preview', 'defe-custom-tables'); ?>
            </button>

            <button type="button" class="button button-primary defe-save-table-btn" id="defe-save-btn">
                <span class="dashicons dashicons-saved"></span> <?php esc_html_e('Save Table', 'defe-custom-tables'); ?>
            </button>
        </div>
    </div>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'saved') : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Table saved successfully!', 'defe-custom-tables'); ?></p></div>
    <?php endif; ?>

    <!-- Choice Banner for New Tables -->
    <?php if ($is_new) : ?>
        <div class="defe-choice-banner" id="defe-choice-banner">
            <div class="defe-choice-card" id="defe-choice-manual">
                <div class="defe-choice-icon"><span class="dashicons dashicons-editor-table"></span></div>
                <h3><?php esc_html_e('Create Table Manually', 'defe-custom-tables'); ?></h3>
                <p><?php esc_html_e('Build a custom table from scratch with full control over rows, columns, and styles.', 'defe-custom-tables'); ?></p>
                <button type="button" class="button button-secondary defe-choice-dismiss-btn"><?php esc_html_e('Start Building', 'defe-custom-tables'); ?></button>
            </div>
            <div class="defe-choice-divider"><?php esc_html_e('OR', 'defe-custom-tables'); ?></div>
            <div class="defe-choice-card defe-choice-card-import" id="defe-choice-image">
                <div class="defe-choice-icon"><span class="dashicons dashicons-camera"></span></div>
                <h3><?php esc_html_e('Import Table From Image', 'defe-custom-tables'); ?></h3>
                <p><?php esc_html_e('Upload a screenshot/photo of any table to automatically extract editable rows and columns.', 'defe-custom-tables'); ?></p>
                <button type="button" class="button button-primary" id="defe-choice-open-import"><?php esc_html_e('Upload & Extract Image', 'defe-custom-tables'); ?></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Builder Grid & Sidebar Form -->
    <form id="defe-builder-form" onsubmit="return false;">
        <!-- Hidden JSON store -->
        <input type="hidden" id="defe_custom_table_raw_data" name="defe_custom_table_raw_data" value="<?php echo esc_attr($table_json); ?>">
        <input type="hidden" id="defe_custom_table_id" name="defe_custom_table_id" value="<?php echo esc_attr($table['id']); ?>">

        <div class="defe-builder-layout">
            <!-- Left Main Column: Builder Grid & Content -->
            <div class="defe-builder-main">
                
                <!-- Table General Info Card -->
                <div class="defe-card">
                    <div class="defe-card-header">
                        <h2><?php esc_html_e('Table Details', 'defe-custom-tables'); ?></h2>
                    </div>
                    <div class="defe-card-body">
                        <div class="defe-form-row">
                            <div class="defe-form-col defe-col-8">
                                <label for="defe_custom_table_name"><strong><?php esc_html_e('Table Name', 'defe-custom-tables'); ?></strong> <span class="description">(<?php esc_html_e('Admin identification and optional frontend title', 'defe-custom-tables'); ?>)</span></label>
                                <input type="text" id="defe_custom_table_name" name="name" class="widefat" value="<?php echo esc_attr($table['name']); ?>" placeholder="<?php esc_attr_e('e.g. Our Achievements / Capital Structure', 'defe-custom-tables'); ?>" required>
                            </div>
                            <div class="defe-form-col defe-col-4 defe-checkbox-col">
                                <label>
                                    <input type="checkbox" id="defe_show_title" name="show_title" value="1" <?php checked(!empty($table['settings']['show_title'])); ?>>
                                    <strong><?php esc_html_e('Show Title on Frontend', 'defe-custom-tables'); ?></strong>
                                </label>
                            </div>
                        </div>

                        <!-- Description & Intro Text -->
                        <div class="defe-form-row defe-description-row">
                            <div class="defe-form-col defe-col-12">
                                <div class="defe-label-flex">
                                    <label for="defe_custom_table_description"><strong><?php esc_html_e('Description / Intro Content', 'defe-custom-tables'); ?></strong> <span class="description">(<?php esc_html_e('Optional text displayed above the table', 'defe-custom-tables'); ?>)</span></label>
                                    <label class="defe-sub-checkbox">
                                        <input type="checkbox" id="defe_show_description" name="show_description" value="1" <?php checked(!empty($table['settings']['show_description'])); ?>>
                                        <?php esc_html_e('Show Description', 'defe-custom-tables'); ?>
                                    </label>
                                </div>
                                <textarea id="defe_custom_table_description" name="description" rows="3" class="widefat" placeholder="<?php esc_attr_e('e.g. Detail of Loans disbursed to the people of minorities and persons with disabilities...', 'defe-custom-tables'); ?>"><?php echo esc_textarea($table['settings']['description']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Grid Builder Card -->
                <div class="defe-card defe-builder-grid-card">
                    <div class="defe-card-header defe-grid-card-header">
                        <div class="defe-grid-header-left">
                            <h2><?php esc_html_e('Table Grid Builder', 'defe-custom-tables'); ?></h2>
                            <span class="defe-grid-counts" id="defe-grid-counts">
                                <span id="defe-col-count-badge">0</span> <?php esc_html_e('Cols', 'defe-custom-tables'); ?> &times; <span id="defe-row-count-badge">0</span> <?php esc_html_e('Rows', 'defe-custom-tables'); ?>
                            </span>
                        </div>
                        <div class="defe-grid-header-actions">
                            <button type="button" class="button button-secondary" id="defe-add-column-btn">
                                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Add Column', 'defe-custom-tables'); ?>
                            </button>
                            <button type="button" class="button button-primary" id="defe-add-row-btn">
                                <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Add Row', 'defe-custom-tables'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="defe-card-body defe-grid-container">
                        <div class="defe-grid-scroll-wrapper">
                            <table class="defe-interactive-table" id="defe-interactive-table">
                                <!-- Rendered dynamically by admin-builder.js -->
                            </table>
                        </div>
                    </div>

                    <div class="defe-card-footer defe-grid-card-footer">
                        <button type="button" class="button button-secondary button-small" id="defe-add-row-btn-bottom">
                            <span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add Row', 'defe-custom-tables'); ?>
                        </button>
                        <button type="button" class="button button-secondary button-small" id="defe-add-column-btn-bottom">
                            <span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add Column', 'defe-custom-tables'); ?>
                        </button>
                    </div>
                </div>

                <!-- Additional Content Lines Repeater -->
                <div class="defe-card">
                    <div class="defe-card-header defe-label-flex">
                        <h2><?php esc_html_e('Additional Content Lines', 'defe-custom-tables'); ?></h2>
                        <button type="button" class="button button-secondary button-small" id="defe-add-extra-line-btn">
                            <span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add Content Line', 'defe-custom-tables'); ?>
                        </button>
                    </div>
                    <div class="defe-card-body">
                        <p class="description"><?php esc_html_e('Add custom notes, footnotes, data source references or legends before or after the table.', 'defe-custom-tables'); ?></p>
                        <div id="defe-extra-content-repeater" class="defe-repeater-list">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Sidebar: Styling & Table Settings -->
            <div class="defe-builder-sidebar">
                
                <!-- Save Box -->
                <div class="defe-card defe-sidebar-card defe-publish-card">
                    <div class="defe-card-header">
                        <h3><?php esc_html_e('Publish Settings', 'defe-custom-tables'); ?></h3>
                    </div>
                    <div class="defe-card-body">
                        <div class="defe-sidebar-row">
                            <label for="defe_custom_table_status"><strong><?php esc_html_e('Status', 'defe-custom-tables'); ?></strong></label>
                            <select id="defe_custom_table_status" name="status" class="widefat">
                                <option value="publish" <?php selected($table['status'], 'publish'); ?>><?php esc_html_e('Published (Active)', 'defe-custom-tables'); ?></option>
                                <option value="draft" <?php selected($table['status'], 'draft'); ?>><?php esc_html_e('Draft', 'defe-custom-tables'); ?></option>
                            </select>
                        </div>
                        <div class="defe-sidebar-actions">
                            <button type="button" class="button button-primary button-large widefat defe-save-table-btn">
                                <span class="dashicons dashicons-saved"></span> <?php esc_html_e('Save Changes', 'defe-custom-tables'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Header Styling -->
                <div class="defe-card defe-sidebar-card">
                    <div class="defe-card-header">
                        <h3><?php esc_html_e('Header Styling', 'defe-custom-tables'); ?></h3>
                    </div>
                    <div class="defe-card-body">
                        <div class="defe-sidebar-row">
                            <label><?php esc_html_e('Header Background Color', 'defe-custom-tables'); ?></label>
                            <input type="text" class="defe-color-picker" id="defe_header_bg_color" value="<?php echo esc_attr($table['settings']['header_bg_color']); ?>" data-default-color="#009C3B">
                        </div>
                        <div class="defe-sidebar-row">
                            <label><?php esc_html_e('Header Text Color', 'defe-custom-tables'); ?></label>
                            <input type="text" class="defe-color-picker" id="defe_header_text_color" value="<?php echo esc_attr($table['settings']['header_text_color']); ?>" data-default-color="#FFFFFF">
                        </div>
                        <div class="defe-sidebar-row defe-row-split">
                            <div>
                                <label><?php esc_html_e('Font Size', 'defe-custom-tables'); ?></label>
                                <input type="text" id="defe_header_font_size" class="widefat" value="<?php echo esc_attr($table['settings']['header_font_size']); ?>" placeholder="15px">
                            </div>
                            <div>
                                <label><?php esc_html_e('Font Weight', 'defe-custom-tables'); ?></label>
                                <select id="defe_header_font_weight" class="widefat">
                                    <option value="400" <?php selected($table['settings']['header_font_weight'], '400'); ?>>Normal (400)</option>
                                    <option value="600" <?php selected($table['settings']['header_font_weight'], '600'); ?>>Semi-Bold (600)</option>
                                    <option value="700" <?php selected($table['settings']['header_font_weight'], '700'); ?>>Bold (700)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total / Footer Row Settings -->
                <div class="defe-card defe-sidebar-card">
                    <div class="defe-card-header">
                        <h3><?php esc_html_e('Total / Footer Row', 'defe-custom-tables'); ?></h3>
                    </div>
                    <div class="defe-card-body">
                        <div class="defe-sidebar-row">
                            <label>
                                <input type="checkbox" id="defe_enable_total_row" value="1" <?php checked(!empty($table['settings']['enable_total_row'])); ?>>
                                <strong><?php esc_html_e('Enable Total / Footer Row', 'defe-custom-tables'); ?></strong>
                            </label>
                        </div>
                        <div id="defe-total-row-options" style="<?php echo empty($table['settings']['enable_total_row']) ? 'display:none;' : ''; ?>">
                            <div class="defe-sidebar-row">
                                <label><?php esc_html_e('Calculation Mode', 'defe-custom-tables'); ?></label>
                                <select id="defe_total_mode" class="widefat">
                                    <option value="manual" <?php selected($table['settings']['total_mode'], 'manual'); ?>><?php esc_html_e('Manual Values (Default)', 'defe-custom-tables'); ?></option>
                                    <option value="auto" <?php selected($table['settings']['total_mode'], 'auto'); ?>><?php esc_html_e('Automatic Column Calculations', 'defe-custom-tables'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Manual lets you type exact custom totals. Auto calculates SUM, COUNT or AVERAGE per column.', 'defe-custom-tables'); ?></p>
                            </div>
                            <div class="defe-sidebar-row">
                                <label><?php esc_html_e('Total Row Background', 'defe-custom-tables'); ?></label>
                                <input type="text" class="defe-color-picker" id="defe_total_bg_color" value="<?php echo esc_attr($table['settings']['total_bg_color']); ?>" data-default-color="#F0FDF4">
                            </div>
                            <div class="defe-sidebar-row">
                                <label><?php esc_html_e('Total Row Text Color', 'defe-custom-tables'); ?></label>
                                <input type="text" class="defe-color-picker" id="defe_total_text_color" value="<?php echo esc_attr($table['settings']['total_text_color']); ?>" data-default-color="#009C3B">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Body & Layout Styling -->
                <div class="defe-card defe-sidebar-card">
                    <div class="defe-card-header">
                        <h3><?php esc_html_e('Body & Layout Styling', 'defe-custom-tables'); ?></h3>
                    </div>
                    <div class="defe-card-body">
                        <div class="defe-sidebar-row">
                            <label><?php esc_html_e('Body Text Color', 'defe-custom-tables'); ?></label>
                            <input type="text" class="defe-color-picker" id="defe_body_text_color" value="<?php echo esc_attr($table['settings']['body_text_color']); ?>" data-default-color="#333333">
                        </div>
                        <div class="defe-sidebar-row">
                            <label><?php esc_html_e('Body Background Color', 'defe-custom-tables'); ?></label>
                            <input type="text" class="defe-color-picker" id="defe_body_bg_color" value="<?php echo esc_attr($table['settings']['body_bg_color']); ?>" data-default-color="#FFFFFF">
                        </div>
                        <div class="defe-sidebar-row">
                            <label>
                                <input type="checkbox" id="defe_enable_zebra" value="1" <?php checked(!empty($table['settings']['enable_zebra'])); ?>>
                                <?php esc_html_e('Enable Zebra Rows', 'defe-custom-tables'); ?>
                            </label>
                            <div id="defe-zebra-color-row" style="<?php echo empty($table['settings']['enable_zebra']) ? 'display:none;' : 'margin-top:8px;'; ?>">
                                <label><?php esc_html_e('Alternate Row Color', 'defe-custom-tables'); ?></label>
                                <input type="text" class="defe-color-picker" id="defe_zebra_bg_color" value="<?php echo esc_attr($table['settings']['zebra_bg_color']); ?>" data-default-color="#F9FAFB">
                            </div>
                        </div>
                        <div class="defe-sidebar-row defe-row-split">
                            <div>
                                <label><?php esc_html_e('Cell Padding', 'defe-custom-tables'); ?></label>
                                <input type="text" id="defe_cell_padding" class="widefat" value="<?php echo esc_attr($table['settings']['cell_padding']); ?>" placeholder="12px">
                            </div>
                            <div>
                                <label><?php esc_html_e('Body Font Size', 'defe-custom-tables'); ?></label>
                                <input type="text" id="defe_font_size" class="widefat" value="<?php echo esc_attr($table['settings']['font_size']); ?>" placeholder="14px">
                            </div>
                        </div>
                        <div class="defe-sidebar-row defe-row-split">
                            <div>
                                <label><?php esc_html_e('Vertical Align', 'defe-custom-tables'); ?></label>
                                <select id="defe_vertical_alignment" class="widefat">
                                    <option value="middle" <?php selected($table['settings']['vertical_alignment'], 'middle'); ?>>Middle</option>
                                    <option value="top" <?php selected($table['settings']['vertical_alignment'], 'top'); ?>>Top</option>
                                    <option value="bottom" <?php selected($table['settings']['vertical_alignment'], 'bottom'); ?>>Bottom</option>
                                </select>
                            </div>
                            <div>
                                <label><?php esc_html_e('Default Text Align', 'defe-custom-tables'); ?></label>
                                <select id="defe_default_alignment" class="widefat">
                                    <option value="left" <?php selected($table['settings']['default_alignment'], 'left'); ?>>Left</option>
                                    <option value="center" <?php selected($table['settings']['default_alignment'], 'center'); ?>>Center</option>
                                    <option value="right" <?php selected($table['settings']['default_alignment'], 'right'); ?>>Right</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Borders & Radius -->
                <div class="defe-card defe-sidebar-card">
                    <div class="defe-card-header">
                        <h3><?php esc_html_e('Borders & Corners', 'defe-custom-tables'); ?></h3>
                    </div>
                    <div class="defe-card-body">
                        <div class="defe-sidebar-row">
                            <label><?php esc_html_e('Border Color', 'defe-custom-tables'); ?></label>
                            <input type="text" class="defe-color-picker" id="defe_border_color" value="<?php echo esc_attr($table['settings']['border_color']); ?>" data-default-color="#E5E7EB">
                        </div>
                        <div class="defe-sidebar-row defe-row-split">
                            <div>
                                <label><?php esc_html_e('Border Width', 'defe-custom-tables'); ?></label>
                                <input type="text" id="defe_border_width" class="widefat" value="<?php echo esc_attr($table['settings']['border_width']); ?>" placeholder="1px">
                            </div>
                            <div>
                                <label><?php esc_html_e('Border Style', 'defe-custom-tables'); ?></label>
                                <select id="defe_border_style" class="widefat">
                                    <option value="solid" <?php selected($table['settings']['border_style'], 'solid'); ?>>Solid</option>
                                    <option value="dashed" <?php selected($table['settings']['border_style'], 'dashed'); ?>>Dashed</option>
                                    <option value="dotted" <?php selected($table['settings']['border_style'], 'dotted'); ?>>Dotted</option>
                                    <option value="none" <?php selected($table['settings']['border_style'], 'none'); ?>>None</option>
                                </select>
                            </div>
                        </div>
                        <div class="defe-sidebar-row">
                            <label><?php esc_html_e('Border Radius', 'defe-custom-tables'); ?></label>
                            <input type="text" id="defe_border_radius" class="widefat" value="<?php echo esc_attr($table['settings']['border_radius']); ?>" placeholder="8px">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<!-- ========================================================== -->
<!-- IMAGE IMPORT & OCR MODAL -->
<!-- ========================================================== -->
<div class="defe-modal" id="defe-image-import-modal" style="display:none;">
    <div class="defe-modal-backdrop"></div>
    <div class="defe-modal-dialog defe-modal-lg">
        <div class="defe-modal-content">
            <div class="defe-modal-header">
                <h2><span class="dashicons dashicons-camera"></span> <?php esc_html_e('Import Table From Image (OCR Extraction)', 'defe-custom-tables'); ?></h2>
                <button type="button" class="defe-modal-close-btn">&times;</button>
            </div>
            <div class="defe-modal-body">
                
                <!-- Step 1: Upload / Choose Image -->
                <div class="defe-import-step" id="defe-import-step-upload">
                    <div class="defe-dropzone" id="defe-dropzone">
                        <span class="dashicons dashicons-cloud-upload defe-dropzone-icon"></span>
                        <h3><?php esc_html_e('Drag & Drop Screenshot / Table Image Here', 'defe-custom-tables'); ?></h3>
                        <p><?php esc_html_e('Supports PNG, JPG, JPEG, and WEBP formats.', 'defe-custom-tables'); ?></p>
                        <div class="defe-dropzone-buttons">
                            <input type="file" id="defe-file-input" accept="image/png,image/jpeg,image/webp" style="display:none;">
                            <button type="button" class="button button-primary button-hero" id="defe-select-file-btn">
                                <?php esc_html_e('Select Image From Computer', 'defe-custom-tables'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="defe-media-library-btn">
                                <?php esc_html_e('Choose from Media Library', 'defe-custom-tables'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Image Preview & Preprocessing -->
                <div class="defe-import-step" id="defe-import-step-preview" style="display:none;">
                    <div class="defe-image-preprocess-bar">
                        <div class="defe-preprocess-tools">
                            <button type="button" class="button button-small" id="defe-tool-rotate-left" title="<?php esc_attr_e('Rotate Left 90°', 'defe-custom-tables'); ?>">
                                <span class="dashicons dashicons-undo"></span> <?php esc_html_e('Rotate -90°', 'defe-custom-tables'); ?>
                            </button>
                            <button type="button" class="button button-small" id="defe-tool-rotate-right" title="<?php esc_attr_e('Rotate Right 90°', 'defe-custom-tables'); ?>">
                                <span class="dashicons dashicons-redo"></span> <?php esc_html_e('Rotate +90°', 'defe-custom-tables'); ?>
                            </button>
                            <button type="button" class="button button-small" id="defe-tool-contrast" title="<?php esc_attr_e('Enhance Contrast', 'defe-custom-tables'); ?>">
                                <span class="dashicons dashicons-image-filter"></span> <?php esc_html_e('Enhance Contrast', 'defe-custom-tables'); ?>
                            </button>
                            <button type="button" class="button button-small" id="defe-tool-reset" title="<?php esc_attr_e('Reset Image', 'defe-custom-tables'); ?>">
                                <?php esc_html_e('Reset', 'defe-custom-tables'); ?>
                            </button>
                        </div>
                        <div class="defe-preprocess-actions">
                            <button type="button" class="button button-link defe-change-img-btn"><?php esc_html_e('Change Image', 'defe-custom-tables'); ?></button>
                            <button type="button" class="button button-primary button-large" id="defe-start-ocr-btn">
                                <span class="dashicons dashicons-search"></span> <?php esc_html_e('Extract Table Data', 'defe-custom-tables'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="defe-image-preview-container">
                        <img id="defe-preview-img" src="" alt="Table Preview">
                        <canvas id="defe-preprocess-canvas" style="display:none;"></canvas>
                    </div>
                </div>

                <!-- Step 3: OCR Processing Loader -->
                <div class="defe-import-step" id="defe-import-step-loading" style="display:none;">
                    <div class="defe-ocr-loader">
                        <span class="spinner is-active"></span>
                        <h3 id="defe-ocr-status-text"><?php esc_html_e('Analyzing image and detecting table structure...', 'defe-custom-tables'); ?></h3>
                        <p class="description"><?php esc_html_e('Detecting rows, columns, headers, numbers, and total row...', 'defe-custom-tables'); ?></p>
                        <div class="defe-progress-bar-wrap">
                            <div class="defe-progress-bar" id="defe-ocr-progress" style="width: 25%;"></div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Editable OCR Review & Correction -->
                <div class="defe-import-step" id="defe-import-step-review" style="display:none;">
                    <div class="notice notice-warning inline defe-ocr-warning-notice">
                        <p>
                            <span class="dashicons dashicons-warning"></span>
                            <strong><?php esc_html_e('Please review the extracted data before importing.', 'defe-custom-tables'); ?></strong>
                            <?php esc_html_e('OCR may contain minor typos in numbers or words. You can edit any cell right here or in the main builder.', 'defe-custom-tables'); ?>
                        </p>
                    </div>

                    <div class="defe-ocr-meta-fields">
                        <div class="defe-form-row">
                            <div class="defe-form-col defe-col-8">
                                <label><strong><?php esc_html_e('Detected Title', 'defe-custom-tables'); ?>:</strong></label>
                                <input type="text" id="defe_ocr_detected_name" class="widefat">
                            </div>
                            <div class="defe-form-col defe-col-4">
                                <label>
                                    <input type="checkbox" id="defe_ocr_use_detected_color" value="1">
                                    <?php esc_html_e('Use Detected Header Color', 'defe-custom-tables'); ?>
                                </label>
                                <div id="defe_ocr_color_preview" class="defe-color-chip" style="display:none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="defe-ocr-review-grid-wrapper">
                        <div class="defe-ocr-review-toolbar">
                            <button type="button" class="button button-small" id="defe-ocr-add-col-btn"><span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add Column', 'defe-custom-tables'); ?></button>
                            <button type="button" class="button button-small" id="defe-ocr-add-row-btn"><span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add Row', 'defe-custom-tables'); ?></button>
                        </div>
                        <div class="defe-ocr-grid-scroll">
                            <table class="defe-ocr-review-table" id="defe-ocr-review-table">
                                <!-- Populated by OCR Result -->
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <div class="defe-modal-footer">
                <button type="button" class="button button-secondary defe-modal-close-btn"><?php esc_html_e('Cancel', 'defe-custom-tables'); ?></button>
                <button type="button" class="button button-primary button-large" id="defe-apply-ocr-import-btn" style="display:none;">
                    <span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Import Into Table Builder', 'defe-custom-tables'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- LIVE PREVIEW MODAL -->
<!-- ========================================================== -->
<div class="defe-modal" id="defe-preview-modal" style="display:none;">
    <div class="defe-modal-backdrop"></div>
    <div class="defe-modal-dialog defe-modal-xl">
        <div class="defe-modal-content">
            <div class="defe-modal-header">
                <h2><span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Frontend Live Preview', 'defe-custom-tables'); ?></h2>
                <div class="defe-preview-device-toggle">
                    <button type="button" class="button button-small active" data-device="desktop"><span class="dashicons dashicons-desktop"></span> <?php esc_html_e('Desktop', 'defe-custom-tables'); ?></button>
                    <button type="button" class="button button-small" data-device="tablet"><span class="dashicons dashicons-tablet"></span> <?php esc_html_e('Tablet', 'defe-custom-tables'); ?></button>
                    <button type="button" class="button button-small" data-device="mobile"><span class="dashicons dashicons-smartphone"></span> <?php esc_html_e('Mobile', 'defe-custom-tables'); ?></button>
                </div>
                <button type="button" class="defe-modal-close-btn">&times;</button>
            </div>
            <div class="defe-modal-body defe-preview-body">
                <div class="defe-preview-frame desktop" id="defe-preview-frame">
                    <div id="defe-preview-content-target">
                        <!-- Injected live -->
                    </div>
                </div>
            </div>
            <div class="defe-modal-footer">
                <span class="description"><?php esc_html_e('Simulates frontend shortcode rendering.', 'defe-custom-tables'); ?></span>
                <button type="button" class="button button-secondary defe-modal-close-btn"><?php esc_html_e('Close Preview', 'defe-custom-tables'); ?></button>
            </div>
        </div>
    </div>
</div>
