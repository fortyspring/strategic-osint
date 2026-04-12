<?php
/**
 * حل مشكلة flush-cdn-status-polling.js
 * أضف هذا الكود إلى ملف functions.php في القالب النشط
 */

// منع تحميل السكريبت المشكل
function fix_disable_flush_cdn_polling() {
    wp_dequeue_script('flush-cdn-status-polling');
    wp_deregister_script('flush-cdn-status-polling');
}
add_action('wp_enqueue_scripts', 'fix_disable_flush_cdn_polling', 100);
add_action('admin_enqueue_scripts', 'fix_disable_flush_cdn_polling', 100);

// إذا كان السكريبت مُضافًا عبر wp_localize_script أو inline script
function fix_remove_flush_cdn_inline() {
    global $wp_scripts;
    if (isset($wp_scripts->registered['flush-cdn-status-polling'])) {
        unset($wp_scripts->registered['flush-cdn-status-polling']);
    }
}
add_action('wp_print_scripts', 'fix_remove_flush_cdn_inline', 100);

?>
