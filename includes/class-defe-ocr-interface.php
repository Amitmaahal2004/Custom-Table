<?php
/**
 * OCR Provider Interface
 */

if (!defined('ABSPATH')) {
    exit;
}

interface DEFE_OCR_Interface {

    /**
     * Get provider identifier
     *
     * @return string
     */
    public function get_id();

    /**
     * Get provider human-readable name
     *
     * @return string
     */
    public function get_name();

    /**
     * Extract structured table data from image
     *
     * @param string $image_path_or_url
     * @param array $options
     * @return array|WP_Error Normalized table structure or WP_Error
     */
    public function extract_table($image_path_or_url, $options = array());
}
