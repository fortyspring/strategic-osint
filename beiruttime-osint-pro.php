<?php
/**
 * Plugin Name: Beiruttime OSINT Pro — نظام الرصد الاستخباراتي الموحد V17
 * Plugin URI: https://t.me/osint_lb
 * Description: V17.5.0 — النسخة المعيارية الكاملة: مركز قيادة استخباراتي، بنوك المعلومات، خوارزميات تصنيف متقدمة، مصفوفات الجهات الفاعلة، إعادة هيكلة كاملة PSR-4
 * Version: 17.5.0-Restructured
 * Author: Mohammad Qassem / Beirut Time
 * Author URI: https://t.me/osint_lb
 * Text Domain: beiruttime-osint-pro
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// تعريف ثوابت البرنامج الإضافي
define('BEIRUTTIME_OSINT_PRO_VERSION', '17.5.0-Restructured');
define('BEIRUTTIME_OSINT_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BEIRUTTIME_OSINT_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BEIRUTTIME_OSINT_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * التحميل التلقائي للفئات (PSR-4 المحسن)
 */
spl_autoload_register(function ($class) {
    $prefix = 'Beiruttime\\OSINT\\';
    $base_dir = BEIRUTTIME_OSINT_PRO_PLUGIN_DIR . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    
    // تحويل namespace إلى مسار ملف
    $file_path = str_replace('\\', '/', $relative_class);
    
    // تقسيم المسار للحصول على المجلد واسم الملف
    $parts = explode('/', $file_path);
    $filename = array_pop($parts);
    $subdir = implode('/', $parts);
    
    // تحديد نوع الملف بناءً على اسم الفئة
    $type_prefix = 'class-';
    if (stripos($filename, 'Trait') !== false || stripos($filename, 'trait') === 0) {
        $type_prefix = 'trait-';
    } elseif (stripos($filename, 'Interface') !== false) {
        $type_prefix = 'interface-';
    }
    
    // بناء المسارات المحتملة للملف
    $possible_files = [];
    
    // إذا كان هناك مجلد فرعي محدد (مثل Core\Plugin)
    if (!empty($subdir)) {
        $possible_files[] = $base_dir . $subdir . '/' . $type_prefix . strtolower($filename) . '.php';
    }
    
    // البحث في جميع المجلدات الرئيسية
    $main_dirs = ['core', 'services', 'traits', 'utils', 'admin', 'frontend'];
    foreach ($main_dirs as $dir) {
        $possible_files[] = $base_dir . $dir . '/' . $type_prefix . strtolower($filename) . '.php';
    }
    
    // التحقق من وجود الملف وتحميله
    foreach ($possible_files as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

/**
 * تهيئة البرنامج الإضافي
 */
function beiruttime_osint_pro_init() {
    // تهيئة الفئات الرئيسية
    if (class_exists('Beiruttime\\OSINT\\Core\\Plugin')) {
        $plugin = Beiruttime\OSINT\Core\Plugin::getInstance();
        $plugin->run();
    }

    // تحميل نصوص الترجمة
    load_plugin_textdomain('beiruttime-osint-pro', false, dirname(BEIRUTTIME_OSINT_PRO_PLUGIN_BASENAME) . '/languages');
}
add_action('plugins_loaded', 'beiruttime_osint_pro_init');

/**
 * التفعيل
 */
function beiruttime_osint_pro_activate() {
    if (class_exists('Beiruttime\\OSINT\\Core\\Activation')) {
        Beiruttime\OSINT\Core\Activation::activate();
    }
    update_option('beiruttime_osint_pro_version', BEIRUTTIME_OSINT_PRO_VERSION);
}
register_activation_hook(__FILE__, 'beiruttime_osint_pro_activate');

/**
 * التعطيل
 */
function beiruttime_osint_pro_deactivate() {
    if (class_exists('Beiruttime\\OSINT\\Core\\Deactivation')) {
        Beiruttime\OSINT\Core\Deactivation::deactivate();
    }
}
register_deactivation_hook(__FILE__, 'beiruttime_osint_pro_deactivate');

/**
 * إضافة رابط الإعدادات في صفحة البرامج الإضافية
 */
function beiruttime_osint_pro_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=beiruttime-osint-pro') . '">' . __('الإعدادات', 'beiruttime-osint-pro') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . BEIRUTTIME_OSINT_PRO_PLUGIN_BASENAME, 'beiruttime_osint_pro_add_settings_link');
