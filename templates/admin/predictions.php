<?php
/**
 * قالب صفحة التنبؤات
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('ليس لديك صلاحية الوصول إلى هذه الصفحة.', 'beiruttime-osint-pro'));
}

global $wpdb;
$predictions_count = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}so_predictions"
);
?>

<div class="wrap">
    <h1><?php echo esc_html__('التنبؤات', 'beiruttime-osint-pro'); ?></h1>
    
    <div class="wp-header-end"></div>
    
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2><?php echo esc_html__('حالة نظام التنبؤات', 'beiruttime-osint-pro'); ?></h2>
        
        <table class="widefat" style="max-width: 600px;">
            <tr>
                <td><?php echo esc_html__('عدد التنبؤات المخزنة:', 'beiruttime-osint-pro'); ?></td>
                <td><strong><?php echo intval($predictions_count); ?></strong></td>
            </tr>
            <tr>
                <td><?php echo esc_html__('حالة الجدول:', 'beiruttime-osint-pro'); ?></td>
                <td>
                    <?php if ($predictions_count >= 0): ?>
                        <span style="color: green;">✓ <?php echo esc_html__('نشط', 'beiruttime-osint-pro'); ?></span>
                    <?php else: ?>
                        <span style="color: red;">✗ <?php echo esc_html__('غير نشط', 'beiruttime-osint-pro'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <p style="margin-top: 20px;">
            <?php echo esc_html__('ملاحظة: جدول التنبؤات سيتم ملؤه تلقائياً عند تشغيل محرك التنبؤات.', 'beiruttime-osint-pro'); ?>
        </p>
    </div>
</div>
