<?php
/**
 * فئة قائمة الإدارة
 * 
 * المسؤولة عن إضافة قوائم النظام إلى لوحة تحكم ووردبريس
 * 
 * @package Beiruttime\OSINT\Admin
 */

namespace Beiruttime\OSINT\Admin;

use Beiruttime\OSINT\Traits\Singleton;

/**
 * فئة AdminMenu
 */
class AdminMenu {
    
    use Singleton;
    
    /**
     * تهيئة الفئة
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    /**
     * إضافة قوائم الإدارة
     */
    public function add_admin_menu() {
        // القائمة الرئيسية
        add_menu_page(
            __('نظام الرصد الاستخباراتي', 'beiruttime-osint-pro'),
            __('OSINT Pro', 'beiruttime-osint-pro'),
            'manage_options',
            'beiruttime-osint-pro',
            [$this, 'render_dashboard_page'],
            'dashicons-chart-line',
            30
        );
        
        // صفحة لوحة القيادة
        add_submenu_page(
            'beiruttime-osint-pro',
            __('لوحة القيادة', 'beiruttime-osint-pro'),
            __('لوحة القيادة', 'beiruttime-osint-pro'),
            'manage_options',
            'beiruttime-osint-pro',
            [$this, 'render_dashboard_page']
        );
        
        // صفحة قاعدة البيانات الاستراتيجية
        add_submenu_page(
            'beiruttime-osint-pro',
            __('قاعدة البيانات الاستراتيجية', 'beiruttime-osint-pro'),
            __('قاعدة البيانات', 'beiruttime-osint-pro'),
            'manage_options',
            'strategic-osint-db',
            [$this, 'render_database_page']
        );
        
        // صفحة التصنيف والتحليل
        add_submenu_page(
            'beiruttime-osint-pro',
            __('التصنيف والتحليل', 'beiruttime-osint-pro'),
            __('التصنيف', 'beiruttime-osint-pro'),
            'manage_options',
            'osint-classifier',
            [$this, 'render_classifier_page']
        );
        
        // صفحة التنبؤات
        add_submenu_page(
            'beiruttime-osint-pro',
            __('التنبؤات', 'beiruttime-osint-pro'),
            __('التنبؤات', 'beiruttime-osint-pro'),
            'manage_options',
            'osint-predictions',
            [$this, 'render_predictions_page']
        );
        
        // صفحة الإعدادات
        add_submenu_page(
            'beiruttime-osint-pro',
            __('الإعدادات', 'beiruttime-osint-pro'),
            __('الإعدادات', 'beiruttime-osint-pro'),
            'manage_options',
            'osint-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * عرض صفحة لوحة القيادة
     */
    public function render_dashboard_page() {
        include BEIRUTTIME_OSINT_PRO_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    /**
     * عرض صفحة قاعدة البيانات
     */
    public function render_database_page() {
        include BEIRUTTIME_OSINT_PRO_PLUGIN_DIR . 'templates/admin/database.php';
    }
    
    /**
     * عرض صفحة التصنيف
     */
    public function render_classifier_page() {
        include BEIRUTTIME_OSINT_PRO_PLUGIN_DIR . 'templates/admin/classifier.php';
    }
    
    /**
     * عرض صفحة التنبؤات
     */
    public function render_predictions_page() {
        include BEIRUTTIME_OSINT_PRO_PLUGIN_DIR . 'templates/admin/predictions.php';
    }
    
    /**
     * عرض صفحة الإعدادات
     */
    public function render_settings_page() {
        include BEIRUTTIME_OSINT_PRO_PLUGIN_DIR . 'templates/admin/settings.php';
    }
}
