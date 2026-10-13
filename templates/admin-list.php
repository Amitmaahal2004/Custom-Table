<?php
/**
 * Admin All Tables List Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap defe-admin-wrap">
    <div class="defe-header-bar">
        <div class="defe-header-left">
            <h1 class="wp-heading-inline"><?php esc_html_e('DEFE Custom Tables', 'defe-custom-tables'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=defe-custom-tables-add')); ?>" class="page-title-action defe-btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Add New Table', 'defe-custom-tables'); ?>
            </a>
        </div>
        <div class="defe-header-right">
            <a href="<?php echo esc_url(admin_url('admin.php?page=defe-custom-tables-settings')); ?>" class="button button-secondary">
                <span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('OCR & Settings', 'defe-custom-tables'); ?>
            </a>
        </div>
    </div>

    <?php if (isset($_GET['message'])) : ?>
        <?php if ($_GET['message'] === 'deleted') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Table deleted successfully.', 'defe-custom-tables'); ?></p></div>
        <?php elseif ($_GET['message'] === 'duplicated') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Table duplicated successfully! You are now editing the copy.', 'defe-custom-tables'); ?></p></div>
        <?php elseif ($_GET['message'] === 'saved') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Table saved successfully!', 'defe-custom-tables'); ?></p></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="defe-filter-bar">
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="defe-custom-tables">
            <div class="defe-search-box">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search tables...', 'defe-custom-tables'); ?>">
                <button type="submit" class="button"><span class="dashicons dashicons-search"></span> <?php esc_html_e('Search', 'defe-custom-tables'); ?></button>
                <?php if (!empty($search)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=defe-custom-tables')); ?>" class="button"><?php esc_html_e('Clear', 'defe-custom-tables'); ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="defe-table-card">
        <table class="wp-list-table widefat fixed striped defe-list-table">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-id" style="width: 70px;"><?php esc_html_e('ID', 'defe-custom-tables'); ?></th>
                    <th scope="col" class="manage-column column-title"><?php esc_html_e('Table Name', 'defe-custom-tables'); ?></th>
                    <th scope="col" class="manage-column column-shortcode" style="width: 260px;"><?php esc_html_e('Shortcode', 'defe-custom-tables'); ?></th>
                    <th scope="col" class="manage-column column-cols" style="width: 100px; text-align: center;"><?php esc_html_e('Columns', 'defe-custom-tables'); ?></th>
                    <th scope="col" class="manage-column column-rows" style="width: 100px; text-align: center;"><?php esc_html_e('Rows', 'defe-custom-tables'); ?></th>
                    <th scope="col" class="manage-column column-status" style="width: 110px; text-align: center;"><?php esc_html_e('Status', 'defe-custom-tables'); ?></th>
                    <th scope="col" class="manage-column column-actions" style="width: 220px; text-align: right;"><?php esc_html_e('Actions', 'defe-custom-tables'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tables)) : ?>
                    <tr>
                        <td colspan="7" class="defe-no-items">
                            <div class="defe-empty-state">
                                <span class="dashicons dashicons-editor-table"></span>
                                <h3><?php esc_html_e('No custom tables found.', 'defe-custom-tables'); ?></h3>
                                <p><?php esc_html_e('Create your first table manually or extract from an image screenshot in seconds.', 'defe-custom-tables'); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=defe-custom-tables-add')); ?>" class="button button-primary button-hero">
                                    <?php esc_html_e('Create Table', 'defe-custom-tables'); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($tables as $t) : 
                        $shortcode_str = sprintf('[defe_table id="%d"]', $t['id']);
                        $edit_url = admin_url('admin.php?page=defe-custom-tables-add&table_id=' . $t['id']);
                        $duplicate_url = wp_nonce_url(admin_url('admin.php?page=defe-custom-tables&action=duplicate&table_id=' . $t['id']), 'defe_duplicate_' . $t['id']);
                        $delete_url = wp_nonce_url(admin_url('admin.php?page=defe-custom-tables&action=delete&table_id=' . $t['id']), 'defe_delete_' . $t['id']);
                    ?>
                        <tr id="defe-row-<?php echo esc_attr($t['id']); ?>">
                            <td class="column-id"><strong>#<?php echo esc_html($t['id']); ?></strong></td>
                            <td class="column-title">
                                <strong><a href="<?php echo esc_url($edit_url); ?>" class="defe-table-title-link"><?php echo esc_html($t['name']); ?></a></strong>
                                <?php if (!empty($t['settings']['description'])) : ?>
                                    <div class="defe-table-desc-preview"><?php echo esc_html(wp_trim_words($t['settings']['description'], 12)); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="column-shortcode">
                                <div class="defe-shortcode-pill">
                                    <code><?php echo esc_html($shortcode_str); ?></code>
                                    <button type="button" class="button button-small defe-copy-shortcode-btn" data-shortcode="<?php echo esc_attr($shortcode_str); ?>" title="<?php esc_attr_e('Copy Shortcode', 'defe-custom-tables'); ?>">
                                        <span class="dashicons dashicons-admin-page"></span> <?php esc_html_e('Copy', 'defe-custom-tables'); ?>
                                    </button>
                                </div>
                            </td>
                            <td class="column-cols" style="text-align: center;">
                                <span class="defe-badge"><?php echo esc_html($t['column_count']); ?></span>
                            </td>
                            <td class="column-rows" style="text-align: center;">
                                <span class="defe-badge"><?php echo esc_html($t['row_count']); ?></span>
                            </td>
                            <td class="column-status" style="text-align: center;">
                                <span class="defe-status-tag defe-status-<?php echo esc_attr($t['status']); ?>">
                                    <?php echo esc_html(ucfirst($t['status'])); ?>
                                </span>
                            </td>
                            <td class="column-actions" style="text-align: right;">
                                <div class="defe-action-buttons">
                                    <a href="<?php echo esc_url($edit_url); ?>" class="button button-small button-secondary defe-btn-edit">
                                        <span class="dashicons dashicons-edit"></span> <?php esc_html_e('Edit', 'defe-custom-tables'); ?>
                                    </a>
                                    <a href="<?php echo esc_url($duplicate_url); ?>" class="button button-small defe-btn-duplicate" title="<?php esc_attr_e('Duplicate Table', 'defe-custom-tables'); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </a>
                                    <button type="button" class="button button-small defe-btn-delete" data-id="<?php echo esc_attr($t['id']); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('defe_admin_nonce')); ?>" title="<?php esc_attr_e('Delete Table', 'defe-custom-tables'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo sprintf(esc_html__('%d items', 'defe-custom-tables'), $total_items); ?></span>
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $paged,
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
