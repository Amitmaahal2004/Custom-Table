<?php
/**
 * Storage handler for DEFE Custom Tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class DEFE_Storage {

    /**
     * Database table name without prefix
     */
    const TABLE_NAME = 'defe_custom_tables';

    /**
     * Get full table name with prefix
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Default table configuration structure
     */
    public static function get_default_settings() {
        return array(
            'show_title' => 1,
            'show_description' => 1,
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
            'total_mode' => 'manual', // 'manual' or 'auto'
            'total_bg_color' => '#F0FDF4',
            'total_text_color' => '#009C3B',
            'total_font_weight' => '700',
            'extra_content' => array(), // array of ['content' => '', 'position' => 'before'|'after']
        );
    }

    /**
     * Default initial table structure for Add New
     */
    public static function get_default_table_data() {
        return array(
            'name' => __('New Custom Table', 'defe-custom-tables'),
            'status' => 'publish',
            'columns' => array(
                array('id' => 'col_1', 'name' => __('Header 1', 'defe-custom-tables'), 'width' => 'auto', 'align' => 'left', 'total_calc' => 'none'),
                array('id' => 'col_2', 'name' => __('Header 2', 'defe-custom-tables'), 'width' => 'auto', 'align' => 'left', 'total_calc' => 'none'),
                array('id' => 'col_3', 'name' => __('Header 3', 'defe-custom-tables'), 'width' => 'auto', 'align' => 'left', 'total_calc' => 'none'),
            ),
            'rows' => array(
                array('id' => 'row_1', 'cells' => array('Cell 1-1', 'Cell 1-2', 'Cell 1-3')),
                array('id' => 'row_2', 'cells' => array('Cell 2-1', 'Cell 2-2', 'Cell 2-3')),
                array('id' => 'row_3', 'cells' => array('Cell 3-1', 'Cell 3-2', 'Cell 3-3')),
            ),
            'total_row' => array(
                'enabled' => 0,
                'cells' => array(__('Total', 'defe-custom-tables'), '', ''),
            ),
            'settings' => self::get_default_settings(),
        );
    }

    /**
     * Get a single table by ID
     *
     * @param int $id
     * @return array|null
     */
    public function get_table($id) {
        global $wpdb;
        $id = absint($id);
        if (!$id) {
            return null;
        }

        $table_name = self::get_table_name();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id), ARRAY_A);

        if (!$row) {
            return null;
        }

        return $this->format_table_row($row);
    }

    /**
     * Format raw DB row into structured table array
     */
    public function format_table_row($row) {
        $data = json_decode($row['data_json'], true);
        if (!is_array($data)) {
            $data = array();
        }

        $settings = isset($data['settings']) && is_array($data['settings']) 
            ? wp_parse_args($data['settings'], self::get_default_settings()) 
            : self::get_default_settings();

        $columns = isset($data['columns']) && is_array($data['columns']) ? $data['columns'] : array();
        $rows = isset($data['rows']) && is_array($data['rows']) ? $data['rows'] : array();
        $total_row = isset($data['total_row']) && is_array($data['total_row']) ? $data['total_row'] : array(
            'enabled' => 0,
            'cells' => array()
        );

        return array(
            'id' => absint($row['id']),
            'name' => $row['name'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'columns' => $columns,
            'rows' => $rows,
            'total_row' => $total_row,
            'settings' => $settings,
            'column_count' => count($columns),
            'row_count' => count($rows),
        );
    }

    /**
     * List all tables with search & pagination
     */
    public function get_tables($args = array()) {
        global $wpdb;

        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
            'search' => '',
            'status' => '',
        );

        $args = wp_parse_args($args, $defaults);
        $table_name = self::get_table_name();

        $where = array("1=1");
        $params = array();

        if (!empty($args['search'])) {
            $where[] = "name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $params[] = $args['status'];
        }

        $where_sql = implode(' AND ', $where);
        $order_by = in_array(strtolower($args['orderby']), array('id', 'name', 'created_at', 'updated_at')) ? $args['orderby'] : 'id';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $limit = absint($args['limit']);
        $offset = absint($args['offset']);

        $sql = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY {$order_by} {$order} LIMIT {$offset}, {$limit}";

        if (!empty($params)) {
            $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
            $total_sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
            $total = $wpdb->get_var($wpdb->prepare($total_sql, $params));
        } else {
            $rows = $wpdb->get_results($sql, ARRAY_A);
            $total_sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
            $total = $wpdb->get_var($total_sql);
        }

        $items = array();
        if ($rows) {
            foreach ($rows as $r) {
                $items[] = $this->format_table_row($r);
            }
        }

        return array(
            'items' => $items,
            'total' => absint($total),
            'limit' => $limit,
            'offset' => $offset,
        );
    }

    /**
     * Save (insert or update) table data
     *
     * @param array $data
     * @param int|null $id
     * @return int|WP_Error ID on success
     */
    public function save_table($data, $id = null) {
        global $wpdb;
        $table_name = self::get_table_name();

        $name = !empty($data['name']) ? sanitize_text_field($data['name']) : __('Untitled Table', 'defe-custom-tables');
        $status = !empty($data['status']) && in_array($data['status'], array('publish', 'draft')) ? $data['status'] : 'publish';

        // Sanitize columns
        $sanitized_columns = array();
        if (!empty($data['columns']) && is_array($data['columns'])) {
            foreach ($data['columns'] as $index => $col) {
                $col_id = !empty($col['id']) ? sanitize_key($col['id']) : 'col_' . ($index + 1);
                $col_name = isset($col['name']) ? wp_kses_post($col['name']) : '';
                $col_width = !empty($col['width']) ? sanitize_text_field($col['width']) : 'auto';
                $col_align = !empty($col['align']) && in_array($col['align'], array('left', 'center', 'right')) ? $col['align'] : 'left';
                $col_calc = !empty($col['total_calc']) && in_array($col['total_calc'], array('none', 'sum', 'count', 'avg')) ? $col['total_calc'] : 'none';

                $sanitized_columns[] = array(
                    'id' => $col_id,
                    'name' => $col_name,
                    'width' => $col_width,
                    'align' => $col_align,
                    'total_calc' => $col_calc,
                );
            }
        }

        // Sanitize rows
        $sanitized_rows = array();
        if (!empty($data['rows']) && is_array($data['rows'])) {
            foreach ($data['rows'] as $r_idx => $row) {
                $row_id = !empty($row['id']) ? sanitize_key($row['id']) : 'row_' . ($r_idx + 1);
                $cells = array();
                if (isset($row['cells']) && is_array($row['cells'])) {
                    foreach ($row['cells'] as $cell) {
                        // Allow safe HTML (br, b, strong, em, span, links) but prevent harmful scripts
                        $cells[] = wp_kses_post((string)$cell);
                    }
                }
                $sanitized_rows[] = array(
                    'id' => $row_id,
                    'cells' => $cells,
                );
            }
        }

        // Sanitize total row
        $sanitized_total_row = array(
            'enabled' => !empty($data['total_row']['enabled']) ? 1 : 0,
            'cells' => array(),
        );
        if (!empty($data['total_row']['cells']) && is_array($data['total_row']['cells'])) {
            foreach ($data['total_row']['cells'] as $t_cell) {
                $sanitized_total_row['cells'][] = wp_kses_post((string)$t_cell);
            }
        }

        // Sanitize settings
        $settings_in = !empty($data['settings']) && is_array($data['settings']) ? $data['settings'] : array();
        $defaults = self::get_default_settings();
        $sanitized_settings = array();

        $sanitized_settings['show_title'] = !empty($settings_in['show_title']) ? 1 : 0;
        $sanitized_settings['show_description'] = !empty($settings_in['show_description']) ? 1 : 0;
        $sanitized_settings['description'] = isset($settings_in['description']) ? wp_kses_post($settings_in['description']) : '';
        $sanitized_settings['header_bg_color'] = !empty($settings_in['header_bg_color']) ? sanitize_hex_color($settings_in['header_bg_color']) ?: $defaults['header_bg_color'] : $defaults['header_bg_color'];
        $sanitized_settings['header_text_color'] = !empty($settings_in['header_text_color']) ? sanitize_hex_color($settings_in['header_text_color']) ?: $defaults['header_text_color'] : $defaults['header_text_color'];
        $sanitized_settings['header_font_size'] = !empty($settings_in['header_font_size']) ? sanitize_text_field($settings_in['header_font_size']) : $defaults['header_font_size'];
        $sanitized_settings['header_font_weight'] = !empty($settings_in['header_font_weight']) ? sanitize_text_field($settings_in['header_font_weight']) : $defaults['header_font_weight'];

        $sanitized_settings['body_text_color'] = !empty($settings_in['body_text_color']) ? sanitize_hex_color($settings_in['body_text_color']) ?: $defaults['body_text_color'] : $defaults['body_text_color'];
        $sanitized_settings['body_bg_color'] = !empty($settings_in['body_bg_color']) ? sanitize_hex_color($settings_in['body_bg_color']) ?: $defaults['body_bg_color'] : $defaults['body_bg_color'];
        $sanitized_settings['enable_zebra'] = !empty($settings_in['enable_zebra']) ? 1 : 0;
        $sanitized_settings['zebra_bg_color'] = !empty($settings_in['zebra_bg_color']) ? sanitize_hex_color($settings_in['zebra_bg_color']) ?: $defaults['zebra_bg_color'] : $defaults['zebra_bg_color'];

        $sanitized_settings['border_color'] = !empty($settings_in['border_color']) ? sanitize_hex_color($settings_in['border_color']) ?: $defaults['border_color'] : $defaults['border_color'];
        $sanitized_settings['border_width'] = !empty($settings_in['border_width']) ? sanitize_text_field($settings_in['border_width']) : $defaults['border_width'];
        $sanitized_settings['border_style'] = !empty($settings_in['border_style']) && in_array($settings_in['border_style'], array('solid', 'dashed', 'dotted', 'none')) ? $settings_in['border_style'] : 'solid';
        $sanitized_settings['border_radius'] = !empty($settings_in['border_radius']) ? sanitize_text_field($settings_in['border_radius']) : $defaults['border_radius'];
        $sanitized_settings['cell_padding'] = !empty($settings_in['cell_padding']) ? sanitize_text_field($settings_in['cell_padding']) : $defaults['cell_padding'];
        $sanitized_settings['font_size'] = !empty($settings_in['font_size']) ? sanitize_text_field($settings_in['font_size']) : $defaults['font_size'];
        $sanitized_settings['footer_font_size'] = !empty($settings_in['footer_font_size']) ? sanitize_text_field($settings_in['footer_font_size']) : $defaults['footer_font_size'];
        $sanitized_settings['vertical_alignment'] = !empty($settings_in['vertical_alignment']) && in_array($settings_in['vertical_alignment'], array('top', 'middle', 'bottom')) ? $settings_in['vertical_alignment'] : 'middle';
        $sanitized_settings['default_alignment'] = !empty($settings_in['default_alignment']) && in_array($settings_in['default_alignment'], array('left', 'center', 'right')) ? $settings_in['default_alignment'] : 'left';

        $sanitized_settings['enable_total_row'] = !empty($settings_in['enable_total_row']) ? 1 : 0;
        $sanitized_settings['total_mode'] = !empty($settings_in['total_mode']) && in_array($settings_in['total_mode'], array('manual', 'auto')) ? $settings_in['total_mode'] : 'manual';
        $sanitized_settings['total_bg_color'] = !empty($settings_in['total_bg_color']) ? sanitize_hex_color($settings_in['total_bg_color']) ?: $defaults['total_bg_color'] : $defaults['total_bg_color'];
        $sanitized_settings['total_text_color'] = !empty($settings_in['total_text_color']) ? sanitize_hex_color($settings_in['total_text_color']) ?: $defaults['total_text_color'] : $defaults['total_text_color'];
        $sanitized_settings['total_font_weight'] = !empty($settings_in['total_font_weight']) ? sanitize_text_field($settings_in['total_font_weight']) : $defaults['total_font_weight'];

        // Additional content lines repeater
        $sanitized_extra = array();
        if (!empty($settings_in['extra_content']) && is_array($settings_in['extra_content'])) {
            foreach ($settings_in['extra_content'] as $line) {
                if (!empty($line['content'])) {
                    $sanitized_extra[] = array(
                        'content' => wp_kses_post($line['content']),
                        'position' => !empty($line['position']) && $line['position'] === 'after' ? 'after' : 'before',
                    );
                }
            }
        }
        $sanitized_settings['extra_content'] = $sanitized_extra;

        $payload = array(
            'columns' => $sanitized_columns,
            'rows' => $sanitized_rows,
            'total_row' => $sanitized_total_row,
            'settings' => $sanitized_settings,
        );

        $json_data = wp_json_encode($payload);
        $current_time = current_time('mysql');

        if ($id && absint($id) > 0) {
            $id = absint($id);
            $wpdb->update(
                $table_name,
                array(
                    'name' => $name,
                    'status' => $status,
                    'data_json' => $json_data,
                    'updated_at' => $current_time,
                ),
                array('id' => $id),
                array('%s', '%s', '%s', '%s'),
                array('%d')
            );
            return $id;
        } else {
            $inserted = $wpdb->insert(
                $table_name,
                array(
                    'name' => $name,
                    'status' => $status,
                    'data_json' => $json_data,
                    'created_at' => $current_time,
                    'updated_at' => $current_time,
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
            if ($inserted === false) {
                return new WP_Error('db_insert_failed', __('Failed to save table in database.', 'defe-custom-tables'));
            }
            return $wpdb->insert_id;
        }
    }

    /**
     * Duplicate an existing table
     *
     * @param int $id
     * @return int|WP_Error New table ID on success
     */
    public function duplicate_table($id) {
        $source = $this->get_table($id);
        if (!$source) {
            return new WP_Error('not_found', __('Source table not found.', 'defe-custom-tables'));
        }

        $new_data = array(
            'name' => sprintf(__('%s (Copy)', 'defe-custom-tables'), $source['name']),
            'status' => $source['status'],
            'columns' => $source['columns'],
            'rows' => $source['rows'],
            'total_row' => $source['total_row'],
            'settings' => $source['settings'],
        );

        return $this->save_table($new_data, null);
    }

    /**
     * Delete a table
     *
     * @param int $id
     * @return bool
     */
    public function delete_table($id) {
        global $wpdb;
        $id = absint($id);
        if (!$id) {
            return false;
        }

        $table_name = self::get_table_name();
        $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));
        return $result !== false;
    }
}
