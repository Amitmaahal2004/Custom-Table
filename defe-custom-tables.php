<?php
/**
 * Plugin Name:       DEFE Custom Tables
 * Plugin URI:        https://example.com/defe-custom-tables
 * Description:       Create and manage unlimited independent, responsive custom tables with manual builder and AI/OCR image extraction.
 * Version:           1.0.0
 * Author:            DEFE Custom Tables Team
 * Author URI:        https://example.com
 * Text Domain:       defe-custom-tables
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Plugin constants
define('DEFE_VERSION', '1.0.0');
define('DEFE_PLUGIN_FILE', __FILE__);
define('DEFE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEFE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DEFE_DB_VERSION', '1.0.0');

// Include required classes
require_once DEFE_PLUGIN_DIR . 'includes/class-defe-storage.php';
require_once DEFE_PLUGIN_DIR . 'includes/class-defe-activator.php';
require_once DEFE_PLUGIN_DIR . 'includes/class-defe-assets.php';
require_once DEFE_PLUGIN_DIR . 'includes/class-defe-renderer.php';
require_once DEFE_PLUGIN_DIR . 'includes/class-defe-shortcode.php';
require_once DEFE_PLUGIN_DIR . 'includes/class-defe-ocr-interface.php';
require_once DEFE_PLUGIN_DIR . 'includes/class-defe-ocr-provider.php';
require_once DEFE_PLUGIN_DIR . 'includes/class-defe-image-import.php';
require_once DEFE_PLUGIN_DIR . 'includes/class-defe-table-builder.php';
require_once DEFE_PLUGIN_DIR . 'includes/class-defe-admin.php';

/**
 * Main Plugin Class
 */
final class DEFE_Custom_Tables {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Storage handler
     */
    public $storage;

    /**
     * Admin handler
     */
    public $admin;

    /**
     * Renderer handler
     */
    public $renderer;

    /**
     * Shortcode handler
     */
    public $shortcode;

    /**
     * Assets handler
     */
    public $assets;

    /**
     * Image Import handler
     */
    public $image_import;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->storage = new DEFE_Storage();
        $this->assets = new DEFE_Assets();
        $this->renderer = new DEFE_Renderer($this->storage);
        $this->shortcode = new DEFE_Shortcode($this->renderer, $this->assets);
        $this->image_import = new DEFE_Image_Import($this->storage);
        $this->admin = new DEFE_Admin($this->storage, $this->renderer, $this->image_import, $this->assets);

        // Activation / Deactivation hooks
        register_activation_hook(DEFE_PLUGIN_FILE, array('DEFE_Activator', 'activate'));
        register_deactivation_hook(DEFE_PLUGIN_FILE, array('DEFE_Activator', 'deactivate'));

        // Initialize plugin hooks
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('defe-custom-tables', false, dirname(plugin_basename(DEFE_PLUGIN_FILE)) . '/languages');

        // Check db upgrades if needed
        DEFE_Activator::check_db_upgrade();
    }
}

// Instantiate plugin
function defe_init() {
    return DEFE_Custom_Tables::get_instance();
}
defe_init();
