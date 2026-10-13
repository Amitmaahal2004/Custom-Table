<?php
/**
 * Admin handler and screens for DEFE Custom Tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class DEFE_Admin {

    /**
     * Storage instance
     */
    protected $storage;

    /**
     * Renderer instance
     */
    protected $renderer;

    /**
     * Image import instance
     */
    protected $image_import;

    /**
     * Assets instance
     */
    protected $assets;

    /**
     * Constructor
     */
    public function __construct(DEFE_Storage $storage, DEFE_Renderer $renderer, DEFE_Image_Import $image_import, DEFE_Assets $assets) {
        $this->storage = $storage;
        $this->renderer = $renderer;
        $this->image_import = $image_import;
        $this->assets = $assets;

        add_action('admin_menu', array($this, 'register_admin_menus'));
        add_action('admin_init', array($this, 'handle_admin_actions'));

        // AJAX endpoints
        add_action('wp_ajax_defe_save_table', array($this, 'ajax_save_table'));
        add_action('wp_ajax_defe_duplicate_table', array($this, 'ajax_duplicate_table'));
        add_action('wp_ajax_defe_delete_table', array($this, 'ajax_delete_table'));
        add_action('wp_ajax_defe_preview_table', array($this, 'ajax_preview_table'));
        add_action('wp_ajax_defe_save_settings', array($this, 'ajax_save_settings'));
    }

    /**
     * Register Admin Menus and Submenus
     */
    public function register_admin_menus() {
        $parent_slug = 'defe-custom-tables';

        add_menu_page(
            __('DEFE Custom Tables', 'defe-custom-tables'),
            __('DEFE Custom Tables', 'defe-custom-tables'),
            'manage_options',
            $parent_slug,
            array($this, 'render_all_tables_page'),
            'dashicons-editor-table',
            30
        );

        add_submenu_page(
            $parent_slug,
            __('All Tables', 'defe-custom-tables'),
            __('All Tables', 'defe-custom-tables'),
            'manage_options',
            $parent_slug,
            array($this, 'render_all_tables_page')
        );

        add_submenu_page(
            $parent_slug,
            __('Add New Table', 'defe-custom-tables'),
            __('Add New', 'defe-custom-tables'),
            'manage_options',
            'defe-custom-tables-add',
            array($this, 'render_builder_page')
        );

        add_submenu_page(
            $parent_slug,
            __('Settings', 'defe-custom-tables'),
            __('Settings', 'defe-custom-tables'),
            'manage_options',
            'defe-custom-tables-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Handle non-AJAX actions like direct delete/duplicate fallback with nonces
     */
    public function handle_admin_actions() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'defe-custom-tables') === false) {
            return;
        }

        if (isset($_GET['action']) && isset($_GET['table_id'])) {
            $action = sanitize_key($_GET['action']);
            $table_id = absint($_GET['table_id']);

            if ($action === 'duplicate' && check_admin_referer('defe_duplicate_' . $table_id)) {
                $new_id = $this->storage->duplicate_table($table_id);
                if (!is_wp_error($new_id)) {
                    wp_safe_redirect(admin_url('admin.php?page=defe-custom-tables-add&table_id=' . $new_id . '&message=duplicated'));
                    exit;
                }
            }

            if ($action === 'delete' && check_admin_referer('defe_delete_' . $table_id)) {
                $this->storage->delete_table($table_id);
                wp_safe_redirect(admin_url('admin.php?page=defe-custom-tables&message=deleted'));
                exit;
            }
        }
    }

    /**
     * Render All Tables screen
     */
    public function render_all_tables_page() {
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $limit = 20;
        $offset = ($paged - 1) * $limit;

        $results = $this->storage->get_tables(array(
            'limit' => $limit,
            'offset' => $offset,
            'search' => $search,
            'orderby' => 'id',
            'order' => 'DESC',
        ));

        $tables = $results['items'];
        $total_items = $results['total'];
        $total_pages = ceil($total_items / $limit);

        include DEFE_PLUGIN_DIR . 'templates/admin-list.php';
    }

    /**
     * Render Add New / Edit Table Builder screen
     */
    public function render_builder_page() {
        $table_id = isset($_GET['table_id']) ? absint($_GET['table_id']) : 0;
        $table = null;

        if ($table_id > 0) {
            $table = $this->storage->get_table($table_id);
        }

        if (!$table) {
            $table = DEFE_Storage::get_default_table_data();
            $table['id'] = 0;
        }

        include DEFE_PLUGIN_DIR . 'templates/admin-builder.php';
    }

    /**
     * Render Settings screen
     */
    public function render_settings_page() {
        $ocr_settings = get_option('defe_ocr_settings', array(
            'provider' => 'browser_ai',
            'api_key' => '',
            'api_endpoint' => '',
            'model' => 'gemini-1.5-flash',
            'max_image_size' => '8',
            'enable_preprocess' => 1,
            'detect_header_color' => 0,
            'detect_total_row' => 1,
        ));

        include DEFE_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * AJAX: Save Table
     */
    public function ajax_save_table() {
        check_ajax_referer('defe_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'defe-custom-tables')));
        }

        $raw_json = isset($_POST['table_data']) ? wp_unslash($_POST['table_data']) : '';
        if (empty($raw_json)) {
            wp_send_json_error(array('message' => __('No table data received.', 'defe-custom-tables')));
        }

        $data = json_decode($raw_json, true);
        if (!is_array($data)) {
            wp_send_json_error(array('message' => __('Invalid table JSON data.', 'defe-custom-tables')));
        }

        $table_id = isset($data['id']) ? absint($data['id']) : 0;
        $saved_id = $this->storage->save_table($data, $table_id > 0 ? $table_id : null);

        if (is_wp_error($saved_id)) {
            wp_send_json_error(array('message' => $saved_id->get_error_message()));
        }

        $saved_table = $this->storage->get_table($saved_id);

        wp_send_json_success(array(
            'id' => $saved_id,
            'shortcode' => sprintf('[defe_table id="%d"]', $saved_id),
            'redirect_url' => admin_url('admin.php?page=defe-custom-tables-add&table_id=' . $saved_id . '&message=saved'),
            'table' => $saved_table,
            'message' => __('Table saved successfully!', 'defe-custom-tables'),
        ));
    }

    /**
     * AJAX: Duplicate Table
     */
    public function ajax_duplicate_table() {
        check_ajax_referer('defe_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'defe-custom-tables')));
        }

        $table_id = isset($_POST['table_id']) ? absint($_POST['table_id']) : 0;
        $new_id = $this->storage->duplicate_table($table_id);

        if (is_wp_error($new_id)) {
            wp_send_json_error(array('message' => $new_id->get_error_message()));
        }

        wp_send_json_success(array(
            'new_id' => $new_id,
            'redirect_url' => admin_url('admin.php?page=defe-custom-tables-add&table_id=' . $new_id . '&message=duplicated'),
            'message' => __('Table duplicated successfully!', 'defe-custom-tables'),
        ));
    }

    /**
     * AJAX: Delete Table
     */
    public function ajax_delete_table() {
        check_ajax_referer('defe_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'defe-custom-tables')));
        }

        $table_id = isset($_POST['table_id']) ? absint($_POST['table_id']) : 0;
        $success = $this->storage->delete_table($table_id);

        if (!$success) {
            wp_send_json_error(array('message' => __('Failed to delete table.', 'defe-custom-tables')));
        }

        wp_send_json_success(array('message' => __('Table deleted successfully.', 'defe-custom-tables')));
    }

    /**
     * AJAX: Generate live preview HTML
     */
    public function ajax_preview_table() {
        check_ajax_referer('defe_admin_nonce', 'nonce');

        $raw_json = isset($_POST['table_data']) ? wp_unslash($_POST['table_data']) : '';
        if (empty($raw_json)) {
            wp_send_json_error(array('message' => __('No data for preview.', 'defe-custom-tables')));
        }

        $data = json_decode($raw_json, true);
        if (!is_array($data)) {
            wp_send_json_error(array('message' => __('Invalid data.', 'defe-custom-tables')));
        }

        // Format temporary row for renderer
        $data['id'] = isset($data['id']) && $data['id'] > 0 ? $data['id'] : 9999;
        $data['data_json'] = $raw_json;
        $data['status'] = 'publish';
        $formatted = $this->storage->format_table_row($data);

        $html = $this->renderer->generate_html($formatted);
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Save Settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('defe_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'defe-custom-tables')));
        }

        $provider = isset($_POST['provider']) ? sanitize_key($_POST['provider']) : 'browser_ai';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : '';
        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : 'gemini-1.5-flash';
        $max_size = isset($_POST['max_image_size']) ? sanitize_text_field($_POST['max_image_size']) : '8';
        $enable_preprocess = !empty($_POST['enable_preprocess']) ? 1 : 0;
        $detect_header_color = !empty($_POST['detect_header_color']) ? 1 : 0;
        $detect_total_row = !empty($_POST['detect_total_row']) ? 1 : 0;

        $settings = array(
            'provider' => $provider,
            'api_key' => $api_key,
            'api_endpoint' => $api_endpoint,
            'model' => $model,
            'max_image_size' => $max_size,
            'enable_preprocess' => $enable_preprocess,
            'detect_header_color' => $detect_header_color,
            'detect_total_row' => $detect_total_row,
        );

        update_option('defe_ocr_settings', $settings);

        wp_send_json_success(array('message' => __('Settings saved successfully!', 'defe-custom-tables')));
    }
}
