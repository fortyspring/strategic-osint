<?php
/**
 * قالب صفحة قاعدة البيانات الاستراتيجية
 */

if (!defined('ABSPATH')) {
    exit;
}

// التحقق من الصلاحيات
if (!current_user_can('manage_options')) {
    wp_die(__('ليس لديك صلاحية الوصول إلى هذه الصفحة.', 'beiruttime-osint-pro'));
}

// معالجة إعادة التحليل (AJAX يتم التعامل معه في class-ajax-handlers.php)
?>

<div class="wrap">
    <h1><?php echo esc_html__('قاعدة البيانات الاستراتيجية', 'beiruttime-osint-pro'); ?></h1>
    
    <div class="wp-header-end"></div>
    
    <div id="so-database-container" style="margin-top: 20px;">
        <!-- رسالة التنبيه -->
        <div id="so-admin-notice" class="notice notice-info" style="display: none;">
            <p><span id="so-notice-message"></span></p>
        </div>
        
        <!-- لوحة التحكم -->
        <div class="card" style="max-width: 800px; margin-bottom: 20px;">
            <h2><?php echo esc_html__('إعادة التحليل الكامل', 'beiruttime-osint-pro'); ?></h2>
            <p><?php echo esc_html__('إعادة تصنيف وتحليل جميع الأخبار في قاعدة البيانات باستخدام خوارزميات الذكاء الاصطناعي المحدثة.', 'beiruttime-osint-pro'); ?></p>
            
            <form id="reanalyze-form" method="post">
                <?php wp_nonce_field('so_reanalyze_action', 'so_reanalyze_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('وضع المعالجة', 'beiruttime-osint-pro'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="processing_mode" value="continue" checked>
                                <?php echo esc_html__('متابعة من آخر نقطة', 'beiruttime-osint-pro'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="processing_mode" value="restart">
                                <?php echo esc_html__('إعادة البدء من البداية', 'beiruttime-osint-pro'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('حجم الدفعة', 'beiruttime-osint-pro'); ?></th>
                        <td>
                            <input type="number" name="batch_size" value="50" min="10" max="500" step="10" 
                                   style="width: 100px;">
                            <p class="description"><?php echo esc_html__('عدد الأخبار التي ستتم معالجتها في كل دفعة.', 'beiruttime-osint-pro'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="button" id="start-reanalyze-btn" class="button button-primary button-large">
                        <?php echo esc_html__('🚀 بدء إعادة التحليل', 'beiruttime-osint-pro'); ?>
                    </button>
                    <button type="button" id="stop-reanalyze-btn" class="button button-secondary button-large" disabled>
                        <?php echo esc_html__('⏹️ إيقاف', 'beiruttime-osint-pro'); ?>
                    </button>
                </p>
            </form>
            
            <!-- شريط التقدم -->
            <div id="progress-container" style="display: none; margin-top: 20px;">
                <p><strong><?php echo esc_html__('التقدم:', 'beiruttime-osint-pro'); ?></strong> <span id="progress-text">0%</span></p>
                <progress id="reanalyze-progress" value="0" max="100" style="width: 100%; height: 30px;"></progress>
                <div id="status-details" style="margin-top: 10px; font-family: monospace;"></div>
            </div>
        </div>
        
        <!-- إحصائيات الجدول -->
        <div class="card" style="max-width: 800px;">
            <h2><?php echo esc_html__('إحصائيات قاعدة البيانات', 'beiruttime-osint-pro'); ?></h2>
            <div id="db-stats">
                <p><?php echo esc_html__('جاري تحميل الإحصائيات...', 'beiruttime-osint-pro'); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let isProcessing = false;
    let processingInterval = null;
    
    // بدء إعادة التحليل
    $('#start-reanalyze-btn').on('click', function() {
        if (isProcessing) return;
        
        isProcessing = true;
        $('#start-reanalyze-btn').prop('disabled', true);
        $('#stop-reanalyze-btn').prop('disabled', false);
        $('#progress-container').show();
        $('#so-admin-notice').hide();
        
        startReanalysis();
    });
    
    // إيقاف إعادة التحليل
    $('#stop-reanalyze-btn').on('click', function() {
        if (!isProcessing) return;
        
        isProcessing = false;
        clearInterval(processingInterval);
        $('#start-reanalyze-btn').prop('disabled', false);
        $('#stop-reanalyze-btn').prop('disabled', true);
        
        showNotice('<?php echo esc_js(__('تم إيقاف العملية.', 'beiruttime-osint-pro')); ?>', 'warning');
    });
    
    function startReanalysis() {
        const formData = {
            action: 'so_reanalyze_all',
            nonce: $('#so_reanalyze_nonce').val(),
            mode: $('input[name="processing_mode"]:checked').val(),
            batch_size: parseInt($('input[name="batch_size"]').val())
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                updateProgress(response.data);
                
                if (!response.data.completed) {
                    processingInterval = setTimeout(startReanalysis, 1000);
                } else {
                    isProcessing = false;
                    $('#start-reanalyze-btn').prop('disabled', false);
                    $('#stop-reanalyze-btn').prop('disabled', true);
                    showNotice('<?php echo esc_js(__('اكتملت إعادة التحليل بنجاح!', 'beiruttime-osint-pro')); ?>', 'success');
                }
            } else {
                isProcessing = false;
                $('#start-reanalyze-btn').prop('disabled', false);
                $('#stop-reanalyze-btn').prop('disabled', true);
                showNotice(response.data.message || '<?php echo esc_js(__('حدث خطأ أثناء المعالجة.', 'beiruttime-osint-pro')); ?>', 'error');
            }
        }).fail(function() {
            isProcessing = false;
            $('#start-reanalyze-btn').prop('disabled', false);
            $('#stop-reanalyze-btn').prop('disabled', true);
            showNotice('<?php echo esc_js(__('فشل الاتصال بالخادم.', 'beiruttime-osint-pro')); ?>', 'error');
        });
    }
    
    function updateProgress(data) {
        const percentage = Math.round((data.processed / data.total) * 100);
        $('#reanalyze-progress').val(percentage);
        $('#progress-text').text(percentage + '%');
        $('#status-details').html(
            '<?php echo esc_js(__('معالج:', 'beiruttime-osint-pro')); ?> ' + data.processed + ' / ' + data.total + '<br>' +
            '<?php echo esc_js(__('ناجح:', 'beiruttime-osint-pro')); ?> ' + data.success_count + ' | ' +
            '<?php echo esc_js(__('فشل:', 'beiruttime-osint-pro')); ?> ' + data.failed_count
        );
    }
    
    function showNotice(message, type) {
        const notice = $('#so-admin-notice');
        notice.removeClass('notice-success notice-error notice-warning notice-info');
        notice.addClass('notice-' + type);
        $('#so-notice-message').text(message);
        notice.fadeIn();
        
        if (type !== 'error') {
            setTimeout(() => notice.fadeOut(), 5000);
        }
    }
    
    // تحميل الإحصائيات
    function loadStats() {
        $.post(ajaxurl, {
            action: 'so_get_db_stats',
            nonce: $('#so_reanalyze_nonce').val()
        }, function(response) {
            if (response.success) {
                let html = '<table class="widefat" style="max-width: 600px;">';
                html += '<tr><td><?php echo esc_js(__('إجمالي الأخبار:', 'beiruttime-osint-pro')); ?></td><td><strong>' + response.data.total_posts + '</strong></td></tr>';
                html += '<tr><td><?php echo esc_js(__('المصنفة:', 'beiruttime-osint-pro')); ?></td><td>' + response.data.classified + '</td></tr>';
                html += '<tr><td><?php echo esc_js(__('غير المصنفة:', 'beiruttime-osint-pro')); ?></td><td>' + response.data.unclassified + '</td></tr>';
                html += '</table>';
                $('#db-stats').html(html);
            }
        });
    }
    
    loadStats();
});
</script>
