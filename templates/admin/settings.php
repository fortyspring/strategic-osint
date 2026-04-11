<?php
/**
 * قالب صفحة الإعدادات
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('ليس لديك صلاحية الوصول إلى هذه الصفحة.', 'beiruttime-osint-pro'));
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('إعدادات نظام الرصد الاستخباراتي', 'beiruttime-osint-pro'); ?></h1>
    
    <div class="wp-header-end"></div>
    
    <form method="post" action="options.php">
        <?php settings_fields('osint_settings_group'); ?>
        <?php do_settings_sections('osint-settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php echo esc_html__('إصدار النظام', 'beiruttime-osint-pro'); ?></th>
                <td>
                    <code><?php echo BEIRUTTIME_OSINT_PRO_VERSION; ?></code>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('حالة النظام', 'beiruttime-osint-pro'); ?></th>
                <td>
                    <span style="color: green;">✓ <?php echo esc_html__('نشط ويعمل', 'beiruttime-osint-pro'); ?></span>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2><?php echo esc_html__('معلومات النظام', 'beiruttime-osint-pro'); ?></h2>
        <table class="widefat" style="max-width: 600px;">
            <tr>
                <td><?php echo esc_html__('إصدار ووردبريس:', 'beiruttime-osint-pro'); ?></td>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <td><?php echo esc_html__('إصدار PHP:', 'beiruttime-osint-pro'); ?></td>
                <td><?php echo phpversion(); ?></td>
            </tr>
        </table>
    </div>
</div>
