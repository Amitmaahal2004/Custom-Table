<?php
/**
 * Shortcode handler for DEFE Custom Tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class DEFE_Shortcode {

    /**
     * Renderer instance
     */
    protected $renderer;

    /**
     * Assets instance
     */
    protected $assets;

    /**
     * Constructor
     */
    public function __construct(DEFE_Renderer $renderer, DEFE_Assets $assets) {
        $this->renderer = $renderer;
        $this->assets = $assets;

        add_shortcode('defe_table', array($this, 'render_shortcode'));
        add_shortcode('defe_custom_table', array($this, 'render_shortcode')); // alias
    }

    /**
     * Render shortcode [defe_table id="123"]
     *
     * @param array $atts
     * @return string
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
            ),
            $atts,
            'defe_table'
        );

        $table_id = absint($atts['id']);
        if (!$table_id) {
            return '';
        }

        // Enqueue frontend CSS and JS
        $this->assets->enqueue_frontend_assets();

        return $this->renderer->render($table_id);
    }
}
