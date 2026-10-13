<?php
/**
 * Image Import Handler for DEFE Custom Tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class DEFE_Image_Import {

    /**
     * Storage instance
     */
    protected $storage;

    /**
     * Constructor
     */
    public function __construct(DEFE_Storage $storage) {
        $this->storage = $storage;

        add_action('wp_ajax_defe_process_image_ocr', array($this, 'ajax_process_image_ocr'));
        add_action('wp_ajax_defe_upload_import_image', array($this, 'ajax_upload_import_image'));
    }

    /**
     * AJAX endpoint to handle image upload via media API
     */
    public function ajax_upload_import_image() {
        check_ajax_referer('defe_admin_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'defe-custom-tables')));
        }

        if (empty($_FILES['image_file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'defe-custom-tables')));
        }

        $file = $_FILES['image_file'];
        $allowed_mimes = array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        );

        $file_info = wp_check_filetype($file['name'], $allowed_mimes);
        if (!$file_info['ext']) {
            wp_send_json_error(array('message' => __('Invalid file format. Please upload a PNG, JPG, or WEBP image.', 'defe-custom-tables')));
        }

        $ocr_settings = get_option('defe_ocr_settings', array());
        $max_size_mb = !empty($ocr_settings['max_image_size']) ? floatval($ocr_settings['max_image_size']) : 8;
        if ($file['size'] > ($max_size_mb * 1024 * 1024)) {
            wp_send_json_error(array('message' => sprintf(__('The image is too large (%s MB). Max allowed size is %s MB.', 'defe-custom-tables'), round($file['size'] / (1024 * 1024), 2), $max_size_mb)));
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload('image_file', 0);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        $image_url = wp_get_attachment_url($attachment_id);
        $image_path = get_attached_file($attachment_id);

        wp_send_json_success(array(
            'attachment_id' => $attachment_id,
            'image_url' => $image_url,
            'image_path' => $image_path,
        ));
    }

    /**
     * AJAX endpoint to run OCR on an uploaded image
     */
    public function ajax_process_image_ocr() {
        check_ajax_referer('defe_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'defe-custom-tables')));
        }

        $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;
        $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
        $image_data_uri = isset($_POST['image_data_uri']) ? sanitize_text_field($_POST['image_data_uri']) : '';

        $image_source = '';
        if ($attachment_id) {
            $image_source = get_attached_file($attachment_id);
        }
        if (empty($image_source) && !empty($image_url)) {
            $image_source = $image_url;
        }

        if (empty($image_source) && empty($image_data_uri)) {
            wp_send_json_error(array('message' => __('No image provided for OCR analysis.', 'defe-custom-tables')));
        }

        $provider = DEFE_OCR_Manager::get_provider();

        if ($provider->get_id() === 'browser_ai') {
            // For browser AI, extraction is initiated on client-side
            wp_send_json_success(array(
                'mode' => 'client',
                'provider' => 'browser_ai',
                'image_url' => $image_url,
            ));
        }

        $extracted = $provider->extract_table($image_source);

        if (is_wp_error($extracted)) {
            wp_send_json_error(array(
                'message' => $extracted->get_error_message(),
                'fallback' => 'manual',
            ));
        }

        wp_send_json_success(array(
            'mode' => 'server',
            'provider' => $provider->get_id(),
            'data' => $extracted,
        ));
    }
}
