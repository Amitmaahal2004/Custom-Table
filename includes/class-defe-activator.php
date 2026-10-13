<?php
/**
 * Plugin activation and deactivation handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class DEFE_Activator {

    /**
     * Run activation routine
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::seed_sample_tables();
    }

    /**
     * Create or update database tables
     */
    public static function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . DEFE_Storage::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'publish',
            data_json longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status_idx (status),
            KEY name_idx (name(191))
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('defe_db_version', DEFE_DB_VERSION);
    }

    /**
     * Set default plugin settings
     */
    public static function set_default_options() {
        if (!get_option('defe_ocr_settings')) {
            update_option('defe_ocr_settings', array(
                'provider' => 'browser_ai', // 'browser_ai', 'gemini', 'custom'
                'api_key' => '',
                'api_endpoint' => '',
                'model' => 'gemini-1.5-flash',
                'max_image_size' => '8', // MB
                'enable_preprocess' => 1,
                'detect_header_color' => 0,
                'detect_total_row' => 1,
            ));
        }
    }

    /**
     * Check if DB upgrade is required
     */
    public static function check_db_upgrade() {
        $current_version = get_option('defe_db_version', '0.0.0');
        if (version_compare($current_version, DEFE_DB_VERSION, '<')) {
            self::create_tables();
        }
    }

    /**
     * Seed sample tables matching user requirements if empty
     */
    public static function seed_sample_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . DEFE_Storage::TABLE_NAME;
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        if (intval($count) > 0) {
            return; // Don't overwrite existing tables
        }

        $storage = new DEFE_Storage();

        // Sample Table 1: Authorised Share Capital (Green header, 3 cols)
        $table_1 = array(
            'name' => 'Capital Structure',
            'status' => 'publish',
            'columns' => array(
                array('id' => 'col_1', 'name' => 'Authorised Share Capital', 'width' => '33.33%', 'align' => 'left', 'total_calc' => 'none'),
                array('id' => 'col_2', 'name' => 'Paid up Capital', 'width' => '33.33%', 'align' => 'left', 'total_calc' => 'none'),
                array('id' => 'col_3', 'name' => 'Grant-in-aid', 'width' => '33.34%', 'align' => 'left', 'total_calc' => 'none'),
            ),
            'rows' => array(
                array('id' => 'row_1', 'cells' => array('Rs. 13.00 Cr', '230', '28044965')),
                array('id' => 'row_2', 'cells' => array('GOI Nil,', 'GOI Nil,', 'GOI Nil,')),
                array('id' => 'row_3', 'cells' => array('Govt. DEFE Rs. 13.00 Cr.', 'Govt. DEFE Rs. 11.34 Cr', 'Govt. DEFE Rs. 93.65 Lakhs.')),
            ),
            'total_row' => array(
                'enabled' => 0,
                'cells' => array('', '', ''),
            ),
            'settings' => array(
                'show_title' => 0,
                'show_description' => 0,
                'description' => '',
                'header_bg_color' => '#009C3B',
                'header_text_color' => '#FFFFFF',
                'header_font_size' => '15px',
                'header_font_weight' => '600',
                'body_text_color' => '#333333',
                'body_bg_color' => '#FFFFFF',
                'enable_zebra' => 0,
                'zebra_bg_color' => '#F9FAFB',
                'border_color' => '#E5E7EB',
                'border_width' => '1px',
                'border_style' => 'solid',
                'border_radius' => '8px',
                'cell_padding' => '12px',
                'font_size' => '14px',
                'footer_font_size' => '14px',
                'vertical_alignment' => 'middle',
                'default_alignment' => 'left',
                'enable_total_row' => 0,
                'total_mode' => 'manual',
                'total_bg_color' => '#F0FDF4',
                'total_text_color' => '#009C3B',
                'total_font_weight' => '700',
                'extra_content' => array(),
            ),
        );
        $storage->save_table($table_1);

        // Sample Table 2: Our Achievements (Navy blue header, light blue footer with totals)
        $table_2 = array(
            'name' => 'Our Achievements',
            'status' => 'publish',
            'columns' => array(
                array('id' => 'col_1', 'name' => 'Years', 'width' => '12%', 'align' => 'center', 'total_calc' => 'none'),
                array('id' => 'col_2', 'name' => 'Total No of Beneficiaries of person with disabilities', 'width' => '18%', 'align' => 'left', 'total_calc' => 'none'),
                array('id' => 'col_3', 'name' => 'Total funds released to people of the Minority Communities', 'width' => '18%', 'align' => 'left', 'total_calc' => 'none'),
                array('id' => 'col_4', 'name' => 'Total No of Beneficiaries of person with disabilities', 'width' => '18%', 'align' => 'left', 'total_calc' => 'none'),
                array('id' => 'col_5', 'name' => 'Total Beneficiaries of both the segments', 'width' => '17%', 'align' => 'left', 'total_calc' => 'none'),
                array('id' => 'col_6', 'name' => 'Grand total of loan released to both the segments', 'width' => '17%', 'align' => 'left', 'total_calc' => 'none'),
            ),
            'rows' => array(
                array('id' => 'row_1', 'cells' => array('1997-98', '12', '397600', '', '12', '397600')),
                array('id' => 'row_2', 'cells' => array('1998-99', '196', '7907102', '', '196', '7907102')),
                array('id' => 'row_3', 'cells' => array('1999-00', '133', '7979265', '', '133', '7979265')),
                array('id' => 'row_4', 'cells' => array('2000-01', '43', '3094800', '20', '63', '5114074')),
                array('id' => 'row_5', 'cells' => array('2001-02', '38', '2313199', '33', '71', '4186199')),
            ),
            'total_row' => array(
                'enabled' => 1,
                'cells' => array('Total', '2421', '407381065', '1457', '3878', '73235980'),
            ),
            'settings' => array(
                'show_title' => 1,
                'show_description' => 1,
                'description' => 'Detail of Loans disbursed to the people of minorities and persons with disabilities up to 16.09.2016.',
                'header_bg_color' => '#003366',
                'header_text_color' => '#FFFFFF',
                'header_font_size' => '14px',
                'header_font_weight' => '600',
                'body_text_color' => '#222222',
                'body_bg_color' => '#FFFFFF',
                'enable_zebra' => 1,
                'zebra_bg_color' => '#F8FAFC',
                'border_color' => '#E2E8F0',
                'border_width' => '1px',
                'border_style' => 'solid',
                'border_radius' => '8px',
                'cell_padding' => '10px',
                'font_size' => '13px',
                'footer_font_size' => '14px',
                'vertical_alignment' => 'middle',
                'default_alignment' => 'left',
                'enable_total_row' => 1,
                'total_mode' => 'manual',
                'total_bg_color' => '#EBF8FF',
                'total_text_color' => '#004B87',
                'total_font_weight' => '700',
                'extra_content' => array(
                    array(
                        'content' => '<small>Figures: DEFE Minorities / PWDs State Channelising Agency / Govt. of DEFE</small>',
                        'position' => 'after'
                    )
                ),
            ),
        );
        $storage->save_table($table_2);
    }

    /**
     * Run deactivation routine
     */
    public static function deactivate() {
        // Flush rewrite rules or temporary cleanup if needed
    }
}
