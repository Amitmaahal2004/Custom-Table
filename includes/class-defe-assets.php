<?php
/**
 * Asset management for DEFE Custom Tables
 *
 * @package DEFE_Custom_Tables
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DEFE_Assets
 */
class DEFE_Assets {

	/**
	 * Flag to check if frontend assets were enqueued.
	 *
	 * @var bool
	 */
	private static $frontend_assets_enqueued = false;

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_admin_assets' )
		);

		add_action(
			'wp_enqueue_scripts',
			array( $this, 'register_frontend_assets' )
		);
	}

	/**
	 * Get version for an asset.
	 *
	 * Uses file modification time for automatic cache busting.
	 *
	 * @param string $file Absolute file path.
	 * @return string
	 */
	private function get_asset_version( $file ) {

		if ( file_exists( $file ) ) {
			return (string) filemtime( $file );
		}

		return defined( 'DEFE_VERSION' ) ? DEFE_VERSION : '1.0.0';
	}

	/**
	 * Register frontend assets.
	 *
	 * Assets are registered globally but only enqueued
	 * when the DEFE shortcode is rendered.
	 */
	public function register_frontend_assets() {

		$css_file = DEFE_PLUGIN_DIR . 'assets/css/frontend.css';
		$js_file  = DEFE_PLUGIN_DIR . 'assets/js/frontend.js';

		$css_version = $this->get_asset_version( $css_file );
		$js_version  = $this->get_asset_version( $js_file );

		/*
		 * Frontend CSS.
		 */
		wp_register_style(
			'defe-frontend-css',
			DEFE_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			$css_version
		);

		/*
		 * Frontend JS.
		 */
		wp_register_script(
			'defe-frontend-js',
			DEFE_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			$js_version,
			true
		);
	}

	/**
	 * Enqueue frontend assets when shortcode is executed.
	 */
	public function enqueue_frontend_assets() {

		if ( self::$frontend_assets_enqueued ) {
			return;
		}

		wp_enqueue_style( 'defe-frontend-css' );
		wp_enqueue_script( 'defe-frontend-js' );

		self::$frontend_assets_enqueued = true;
	}

	/**
	 * Enqueue admin assets specifically on plugin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {

		/*
		 * Only load assets on DEFE Custom Tables admin pages.
		 */
		if ( strpos( $hook, 'defe-custom-tables' ) === false ) {
			return;
		}

		/*
		 * Asset files.
		 */
		$admin_css_file = DEFE_PLUGIN_DIR . 'assets/css/admin.css';
		$ocr_js_file    = DEFE_PLUGIN_DIR . 'assets/js/admin-ocr.js';
		$builder_js_file = DEFE_PLUGIN_DIR . 'assets/js/admin-builder.js';
		$admin_js_file  = DEFE_PLUGIN_DIR . 'assets/js/admin.js';

		/*
		 * Automatic cache-busting versions.
		 */
		$admin_css_version = $this->get_asset_version( $admin_css_file );
		$ocr_js_version    = $this->get_asset_version( $ocr_js_file );
		$builder_js_version = $this->get_asset_version( $builder_js_file );
		$admin_js_version  = $this->get_asset_version( $admin_js_file );

		/*
		 * Native WordPress Color Picker.
		 */
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		/*
		 * Native WordPress Media Uploader.
		 */
		wp_enqueue_media();

		/*
		 * Admin CSS.
		 */
		wp_enqueue_style(
			'defe-admin-css',
			DEFE_PLUGIN_URL . 'assets/css/admin.css',
			array( 'wp-color-picker' ),
			$admin_css_version
		);

		/*
		 * Admin OCR Script.
		 */
		wp_enqueue_script(
			'defe-admin-ocr',
			DEFE_PLUGIN_URL . 'assets/js/admin-ocr.js',
			array( 'jquery' ),
			$ocr_js_version,
			true
		);

		/*
		 * Admin Table Builder Script.
		 */
		wp_enqueue_script(
			'defe-admin-builder',
			DEFE_PLUGIN_URL . 'assets/js/admin-builder.js',
			array(
				'jquery',
				'wp-color-picker',
				'defe-admin-ocr',
			),
			$builder_js_version,
			true
		);

		/*
		 * Admin List Script.
		 */
		wp_enqueue_script(
			'defe-admin-js',
			DEFE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			$admin_js_version,
			true
		);

		/*
		 * OCR settings.
		 */
		$ocr_settings = get_option(
			'defe_ocr_settings',
			array()
		);

		$ocr_provider = isset( $ocr_settings['provider'] )
			? sanitize_key( $ocr_settings['provider'] )
			: 'browser_ai';

		/*
		 * Localize admin builder data.
		 */
		wp_localize_script(
			'defe-admin-builder',
			'defe_vars',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'defe_admin_nonce' ),
				'ocr_provider' => $ocr_provider,
				'plugin_url'  => DEFE_PLUGIN_URL,

				'strings' => array(
					'confirm_delete' => __(
						'Are you sure you want to delete this table? This action cannot be undone.',
						'defe-custom-tables'
					),

					'confirm_delete_row' => __(
						'Delete this row?',
						'defe-custom-tables'
					),

					'confirm_delete_col' => __(
						'Delete this column and all its cell data?',
						'defe-custom-tables'
					),

					'shortcode_copied' => __(
						'Shortcode copied to clipboard!',
						'defe-custom-tables'
					),

					'saving' => __(
						'Saving Table...',
						'defe-custom-tables'
					),

					'saved' => __(
						'Table saved successfully!',
						'defe-custom-tables'
					),

					'save_error' => __(
						'Error saving table. Please try again.',
						'defe-custom-tables'
					),

					'ocr_processing' => __(
						'Analyzing image and detecting table structure...',
						'defe-custom-tables'
					),

					'ocr_success' => __(
						'Table structure detected! Please review and edit before importing.',
						'defe-custom-tables'
					),

					'ocr_failed' => __(
						'Could not detect table structure from this image. Please try a clearer or cropped image.',
						'defe-custom-tables'
					),
				),
			)
		);
	}
}