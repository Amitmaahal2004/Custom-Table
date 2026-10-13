<?php
/**
 * Frontend HTML table renderer for DEFE Custom Tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class DEFE_Renderer {

    /**
     * Storage instance
     */
    protected $storage;

    /**
     * Constructor
     */
    public function __construct(DEFE_Storage $storage) {
        $this->storage = $storage;
    }

    /**
     * Render table by ID
     *
     * @param int $table_id
     * @return string Rendered HTML
     */
    public function render($table_id) {
        $table_id = absint($table_id);
        if (!$table_id) {
            return '';
        }

        $table = $this->storage->get_table($table_id);
        if (!$table || $table['status'] !== 'publish') {
            if (current_user_can('edit_posts')) {
                return '<div class="defe-custom-table-notice defe-custom-table-warning">' . 
                    sprintf(esc_html__('DEFE Custom Tables: Table #%d not found or not published.', 'defe-custom-tables'), $table_id) . 
                    '</div>';
            }
            return '';
        }

        return $this->generate_html($table);
    }

    /**
     * Generate complete HTML structure and scoped CSS
     */
    public function generate_html($table) {
        $id = $table['id'];
        $unique_id = 'defe-table-' . $id;
        $settings = $table['settings'];
        $columns = $table['columns'];
        $rows = $table['rows'];
        $total_row = $table['total_row'];

        // Auto calculate totals if enabled and mode is auto
        $calculated_total_cells = array();
        if (!empty($settings['enable_total_row'])) {
            $calculated_total_cells = $this->resolve_total_row_cells($columns, $rows, $total_row, $settings);
        }

        ob_start();
        ?>
        <!-- DEFE Custom Table #<?php echo esc_attr($id); ?> -->
        <style id="<?php echo esc_attr($unique_id); ?>-css">
            #<?php echo esc_attr($unique_id); ?> {
                --defe-header-bg: <?php echo esc_attr($settings['header_bg_color']); ?>;
                --defe-header-color: <?php echo esc_attr($settings['header_text_color']); ?>;
                --defe-header-font-size: <?php echo esc_attr($settings['header_font_size']); ?>;
                --defe-header-font-weight: <?php echo esc_attr($settings['header_font_weight']); ?>;
                --defe-body-color: <?php echo esc_attr($settings['body_text_color']); ?>;
                --defe-body-bg: <?php echo esc_attr($settings['body_bg_color']); ?>;
                --defe-zebra-bg: <?php echo esc_attr($settings['zebra_bg_color']); ?>;
                --defe-border-color: <?php echo esc_attr($settings['border_color']); ?>;
                --defe-border-width: <?php echo esc_attr($settings['border_width']); ?>;
                --defe-border-style: <?php echo esc_attr($settings['border_style']); ?>;
                --defe-border-radius: <?php echo esc_attr($settings['border_radius']); ?>;
                --defe-cell-padding: <?php echo esc_attr($settings['cell_padding']); ?>;
                --defe-font-size: <?php echo esc_attr($settings['font_size']); ?>;
                --defe-footer-font-size: <?php echo esc_attr($settings['footer_font_size']); ?>;
                --defe-vertical-align: <?php echo esc_attr($settings['vertical_alignment']); ?>;
                --defe-total-bg: <?php echo esc_attr($settings['total_bg_color']); ?>;
                --defe-total-color: <?php echo esc_attr($settings['total_text_color']); ?>;
                --defe-total-weight: <?php echo esc_attr($settings['total_font_weight']); ?>;
            }
        </style>

        <div class="defe-custom-table-wrapper" id="<?php echo esc_attr($unique_id); ?>" data-table-id="<?php echo esc_attr($id); ?>">
            
            <?php // Title ?>
            <?php if (!empty($settings['show_title']) && !empty($table['name'])) : ?>
                <h3 class="defe-custom-table-title"><?php echo esc_html($table['name']); ?></h3>
            <?php endif; ?>

            <?php // Description ?>
            <?php if (!empty($settings['show_description']) && !empty($settings['description'])) : ?>
                <div class="defe-custom-table-description">
                    <?php echo wp_kses_post(wpautop($settings['description'])); ?>
                </div>
            <?php endif; ?>

            <?php // Extra content (Before) ?>
            <?php $this->render_extra_content($settings['extra_content'], 'before'); ?>

            <?php // Responsive Scroll Container ?>
            <div class="defe-custom-table-scroll">
                <table class="defe-custom-table <?php echo !empty($settings['enable_zebra']) ? 'defe-custom-table-zebra' : ''; ?>">
                    
                    <?php // Table Header ?>
                    <?php if (!empty($columns)) : ?>
                        <thead>
                            <tr>
                                <?php foreach ($columns as $c_idx => $col) : 
                                    $align = !empty($col['align']) ? $col['align'] : $settings['default_alignment'];
                                    $width_style = (!empty($col['width']) && $col['width'] !== 'auto') ? 'width:' . esc_attr($col['width']) . ';' : '';
                                    $align_style = 'text-align:' . esc_attr($align) . ';';
                                ?>
                                    <th scope="col" class="defe-col-<?php echo esc_attr($c_idx); ?>" style="<?php echo esc_attr($width_style . $align_style); ?>">
                                        <?php echo wp_kses_post($col['name']); ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                    <?php endif; ?>

                    <?php // Table Body ?>
                    <tbody>
                        <?php if (!empty($rows)) : ?>
                            <?php foreach ($rows as $r_idx => $row) : ?>
                                <tr class="defe-row-<?php echo esc_attr($r_idx); ?>">
                                    <?php 
                                    $cells = isset($row['cells']) && is_array($row['cells']) ? $row['cells'] : array();
                                    foreach ($columns as $c_idx => $col) :
                                        $cell_val = isset($cells[$c_idx]) ? $cells[$c_idx] : '';
                                        $align = !empty($col['align']) ? $col['align'] : $settings['default_alignment'];
                                        $align_style = 'text-align:' . esc_attr($align) . ';';
                                    ?>
                                        <td class="defe-cell-<?php echo esc_attr($c_idx); ?>" style="<?php echo esc_attr($align_style); ?>">
                                            <?php echo wp_kses_post(nl2br($cell_val)); ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                    <?php // Total / Footer Row ?>
                    <?php if (!empty($settings['enable_total_row']) && !empty($calculated_total_cells)) : ?>
                        <tfoot>
                            <tr class="defe-total-row">
                                <?php foreach ($columns as $c_idx => $col) : 
                                    $t_val = isset($calculated_total_cells[$c_idx]) ? $calculated_total_cells[$c_idx] : '';
                                    $align = !empty($col['align']) ? $col['align'] : $settings['default_alignment'];
                                    $align_style = 'text-align:' . esc_attr($align) . ';';
                                ?>
                                    <td class="defe-footer-cell-<?php echo esc_attr($c_idx); ?>" style="<?php echo esc_attr($align_style); ?>">
                                        <?php echo wp_kses_post(nl2br($t_val)); ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tfoot>
                    <?php endif; ?>

                </table>
            </div>

            <?php // Extra content (After) ?>
            <?php $this->render_extra_content($settings['extra_content'], 'after'); ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render extra content lines based on position
     */
    protected function render_extra_content($extra_content, $position) {
        if (empty($extra_content) || !is_array($extra_content)) {
            return;
        }

        $items = array();
        foreach ($extra_content as $item) {
            $item_pos = isset($item['position']) && $item['position'] === 'after' ? 'after' : 'before';
            if ($item_pos === $position && !empty($item['content'])) {
                $items[] = $item['content'];
            }
        }

        if (empty($items)) {
            return;
        }

        echo '<div class="defe-custom-table-extra defe-custom-table-extra-' . esc_attr($position) . '">';
        foreach ($items as $c) {
            echo '<div class="defe-custom-table-extra-line">' . wp_kses_post($c) . '</div>';
        }
        echo '</div>';
    }

    /**
     * Resolve total row cells (manual or automated calculation)
     */
    protected function resolve_total_row_cells($columns, $rows, $total_row, $settings) {
        $mode = !empty($settings['total_mode']) ? $settings['total_mode'] : 'manual';
        $manual_cells = isset($total_row['cells']) && is_array($total_row['cells']) ? $total_row['cells'] : array();

        if ($mode === 'manual') {
            return $manual_cells;
        }

        // Automatic mode calculation per column
        $result = array();
        foreach ($columns as $c_idx => $col) {
            $calc = !empty($col['total_calc']) ? $col['total_calc'] : 'none';

            if ($calc === 'none') {
                $result[$c_idx] = isset($manual_cells[$c_idx]) ? $manual_cells[$c_idx] : '';
                continue;
            }

            $numeric_values = array();
            $count = 0;

            foreach ($rows as $row) {
                if (isset($row['cells'][$c_idx])) {
                    $raw = trim(strip_tags($row['cells'][$c_idx]));
                    if ($raw !== '') {
                        $count++;
                        // Clean currency and commas (e.g., "$1,234.50" or "Rs. 13.00")
                        $clean = preg_replace('/[^0-9\.\-]/', '', $raw);
                        if (is_numeric($clean)) {
                            $numeric_values[] = floatval($clean);
                        }
                    }
                }
            }

            if ($calc === 'sum') {
                $sum = array_sum($numeric_values);
                $result[$c_idx] = (floor($sum) == $sum) ? number_format($sum, 0) : number_format($sum, 2);
            } elseif ($calc === 'count') {
                $result[$c_idx] = (string)$count;
            } elseif ($calc === 'avg') {
                if (!empty($numeric_values)) {
                    $avg = array_sum($numeric_values) / count($numeric_values);
                    $result[$c_idx] = (floor($avg) == $avg) ? number_format($avg, 0) : number_format($avg, 2);
                } else {
                    $result[$c_idx] = '0';
                }
            } else {
                $result[$c_idx] = isset($manual_cells[$c_idx]) ? $manual_cells[$c_idx] : '';
            }
        }

        return $result;
    }
}
