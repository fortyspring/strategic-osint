<?php
/**
 * فئة معالجات AJAX
 * 
 * المسؤولة عن معالجة طلبات AJAX للنظام
 * 
 * @package Beiruttime\OSINT\Admin
 */

namespace Beiruttime\OSINT\Admin;

use Beiruttime\OSINT\Traits\Singleton;
use Beiruttime\OSINT\Services\Classifier;
use Beiruttime\OSINT\Services\Newslog;

/**
 * فئة AjaxHandlers
 */
class AjaxHandlers {
    
    use Singleton;
    
    /**
     * تهيئة الفئة
     */
    private function __construct() {
        // إعادة التحليل الكامل
        add_action('wp_ajax_so_reanalyze_all', [$this, 'handle_reanalyze_all']);
        
        // الحصول على إحصائيات قاعدة البيانات
        add_action('wp_ajax_so_get_db_stats', [$this, 'handle_get_db_stats']);
    }
    
    /**
     * معالجة إعادة التحليل الكامل
     */
    public function handle_reanalyze_all() {
        // التحقق من الصلاحيات
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك.', 'beiruttime-osint-pro')]);
        }
        
        // التحقق من Nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'so_reanalyze_action')) {
            wp_send_json_error(['message' => __('رمز الأمان غير صحيح.', 'beiruttime-osint-pro')]);
        }
        
        $mode = isset($_POST['mode']) && $_POST['mode'] === 'restart' ? 'restart' : 'continue';
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;
        
        // الحصول على آخر حالة
        $last_processed = get_option('so_reanalyze_last_position', 0);
        
        if ($mode === 'restart') {
            $last_processed = 0;
            update_option('so_reanalyze_last_position', 0);
        }
        
        // الحصول على إجمالي الأخبار
        global $wpdb;
        $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'");
        
        if ($total_posts == 0) {
            wp_send_json_success([
                'completed' => true,
                'processed' => 0,
                'total' => 0,
                'success_count' => 0,
                'failed_count' => 0
            ]);
        }
        
        // حساب نقطة البداية
        $offset = $last_processed;
        
        // الحصول على دفعة من الأخبار
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title, post_content FROM {$wpdb->posts} 
                 WHERE post_type = 'post' AND post_status = 'publish' 
                 ORDER BY ID ASC 
                 LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            )
        );
        
        if (empty($posts)) {
            // اكتملت المعالجة
            delete_option('so_reanalyze_last_position');
            wp_send_json_success([
                'completed' => true,
                'processed' => $total_posts,
                'total' => $total_posts,
                'success_count' => get_option('so_reanalyze_success_count', 0),
                'failed_count' => get_option('so_reanalyze_failed_count', 0)
            ]);
        }
        
        // معالجة الدفعة
        $success_count = 0;
        $failed_count = 0;
        
        foreach ($posts as $post) {
            try {
                $text = $post->post_title . ' ' . $post->post_content;
                
                // استخدام خدمة التصنيف
                $classifier = Classifier::getInstance();
                $result = $classifier->governorAI([], $text);
                
                // حفظ النتائج في meta
                if (!empty($result)) {
                    update_post_meta($post->ID, '_osint_classification', $result);
                    $success_count++;
                } else {
                    $failed_count++;
                }
            } catch (\Exception $e) {
                $failed_count++;
                error_log('OSINT Reanalyze Error for post ' . $post->ID . ': ' . $e->getMessage());
            }
        }
        
        // تحديث العدادات
        $prev_success = get_option('so_reanalyze_success_count', 0);
        $prev_failed = get_option('so_reanalyze_failed_count', 0);
        update_option('so_reanalyze_success_count', $prev_success + $success_count);
        update_option('so_reanalyze_failed_count', $prev_failed + $failed_count);
        
        // تحديث آخر موقع
        $new_position = $offset + count($posts);
        update_option('so_reanalyze_last_position', $new_position);
        
        wp_send_json_success([
            'completed' => false,
            'processed' => $new_position,
            'total' => $total_posts,
            'success_count' => $prev_success + $success_count,
            'failed_count' => $prev_failed + $failed_count
        ]);
    }
    
    /**
     * معالجة الحصول على إحصائيات قاعدة البيانات
     */
    public function handle_get_db_stats() {
        // التحقق من الصلاحيات
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك.', 'beiruttime-osint-pro')]);
        }
        
        global $wpdb;
        
        $total_posts = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'"
        );
        
        $classified = $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'post' AND p.post_status = 'publish'
             AND pm.meta_key = '_osint_classification'"
        );
        
        $unclassified = $total_posts - $classified;
        
        wp_send_json_success([
            'total_posts' => intval($total_posts),
            'classified' => intval($classified),
            'unclassified' => intval($unclassified)
        ]);
    }
}
