<?php
/**
 * قالب صفحة التصنيف والتحليل
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('ليس لديك صلاحية الوصول إلى هذه الصفحة.', 'beiruttime-osint-pro'));
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('التصنيف والتحليل', 'beiruttime-osint-pro'); ?></h1>
    
    <div class="wp-header-end"></div>
    
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2><?php echo esc_html__('اختبار التصنيف', 'beiruttime-osint-pro'); ?></h2>
        <p><?php echo esc_html__('أدخل نصاً خبرياً لتجربة خوارزميات التصنيف.', 'beiruttime-osint-pro'); ?></p>
        
        <form method="post">
            <?php wp_nonce_field('osint_test_classifier', 'osint_classifier_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('النص الإخباري', 'beiruttime-osint-pro'); ?></th>
                    <td>
                        <textarea name="test_text" rows="5" class="large-text" placeholder="أدخل النص هنا..."></textarea>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="osint_classify_test" class="button button-primary">
                    <?php echo esc_html__('تصنيف النص', 'beiruttime-osint-pro'); ?>
                </button>
            </p>
        </form>
        
        <?php
        if (isset($_POST['osint_classify_test']) && 
            isset($_POST['osint_classifier_nonce']) && 
            wp_verify_nonce($_POST['osint_classifier_nonce'], 'osint_test_classifier')) {
            
            $text = sanitize_textarea_field($_POST['test_text']);
            
            if (!empty($text)) {
                use Beiruttime\OSINT\Services\Classifier;
                
                $classifier = Classifier::getInstance();
                $result = $classifier->governorAI([], $text);
                
                echo '<div class="notice notice-success"><p><strong>' . esc_html__('نتيجة التصنيف:', 'beiruttime-osint-pro') . '</strong></p>';
                echo '<pre>' . esc_html(print_r($result, true)) . '</pre></div>';
            }
        }
        ?>
    </div>
</div>
