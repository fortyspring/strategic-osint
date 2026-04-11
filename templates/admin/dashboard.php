<?php
/**
 * قالب لوحة القيادة
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('لوحة قيادة نظام الرصد الاستخباراتي', 'beiruttime-osint-pro'); ?></h1>
    
    <div class="wp-header-end"></div>
    
    <div class="welcome-panel" style="margin-top: 20px;">
        <div class="welcome-panel-content">
            <h2><?php echo esc_html__('مرحباً بك في Beiruttime OSINT Pro', 'beiruttime-osint-pro'); ?></h2>
            <p class="about-description">
                <?php echo esc_html__('نظام الرصد الاستخباراتي الموحد - الجيل الاحترافي الكامل', 'beiruttime-osint-pro'); ?>
            </p>
            
            <div class="welcome-panel-column-container" style="margin-top: 30px;">
                <div class="welcome-panel-columns">
                    <div class="welcome-panel-column-first">
                        <h3><?php echo esc_html__('القوائم الرئيسية', 'beiruttime-osint-pro'); ?></h3>
                        <ul>
                            <li><a href="<?php echo admin_url('admin.php?page=strategic-osint-db'); ?>" class="button button-primary"><?php echo esc_html__('قاعدة البيانات الاستراتيجية', 'beiruttime-osint-pro'); ?></a></li>
                            <li><a href="<?php echo admin_url('admin.php?page=osint-classifier'); ?>" class="button button-secondary"><?php echo esc_html__('التصنيف والتحليل', 'beiruttime-osint-pro'); ?></a></li>
                            <li><a href="<?php echo admin_url('admin.php?page=osint-predictions'); ?>" class="button button-secondary"><?php echo esc_html__('التنبؤات', 'beiruttime-osint-pro'); ?></a></li>
                            <li><a href="<?php echo admin_url('admin.php?page=osint-settings'); ?>" class="button button-secondary"><?php echo esc_html__('الإعدادات', 'beiruttime-osint-pro'); ?></a></li>
                        </ul>
                    </div>
                    
                    <div class="welcome-panel-column">
                        <h3><?php echo esc_html__('إحصائيات سريعة', 'beiruttime-osint-pro'); ?></h3>
                        <p><?php echo esc_html__('جاري تحميل الإحصائيات...', 'beiruttime-osint-pro'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
