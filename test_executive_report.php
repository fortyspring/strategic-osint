<?php
/**
 * اختبار تقرير تنفيذي - ملف مستقل
 * ضع هذا الملف في مجلد wp-content ثم شغله عبر المتصفح: 
 * your-site.com/wp-content/test_executive_report.php
 */

// تحميل ووردبريس
require_once dirname(__DIR__, 2) . '/wp-load.php';

if (!current_user_can('manage_options')) {
    die('غير مصرح - يجب أن تكون مسؤولاً');
}

global $wpdb;
$table = $wpdb->prefix . 'so_news_events';

// التحقق من وجود بيانات
$count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='published'");
echo "<h1>اختبار التقارير التنفيذية</h1>";
echo "<p>عدد الأحداث المنشورة: <strong>{$count}</strong></p>";

if ($count == 0) {
    echo "<div style='background:#fee;border:1px solid #fcc;padding:15px;border-radius:8px;'>";
    echo "<h3>⚠️ لا توجد أحداث في قاعدة البيانات!</h3>";
    echo "<p>يجب إضافة أحداث تجريبية لاختبار التقرير.</p>";
    echo "<form method='post'>";
    echo "<button type='submit' name='add_test_data' style='padding:10px 20px;background:#dc2626;color:white;border:none;border-radius:6px;cursor:pointer;'>➕ إضافة 10 أحداث تجريبية</button>";
    echo "</form>";
    echo "</div>";
    
    // إضافة بيانات تجريبية عند الضغط
    if (isset($_POST['add_test_data'])) {
        $test_events = [
            ['title' => 'تصعيد ميداني في الجنوب اللبناني', 'region' => 'لبنان', 'actor' => 'حزب الله', 'type' => 'عسكري/أمني', 'score' => 180],
            ['title' => 'ضربة جوية إسرائيلية على دمشق', 'region' => 'سوريا', 'actor' => 'إسرائيل', 'type' => 'عسكري/أمني', 'score' => 200],
            ['title' => 'بيان سياسي من طهران', 'region' => 'إيران', 'actor' => 'إيران', 'type' => 'سياسي', 'score' => 120],
            ['title' => 'اجتماع طارئ للجامعة العربية', 'region' => 'مصر', 'actor' => 'الجامعة العربية', 'type' => 'سياسي', 'score' => 90],
            ['title' => 'مناورات عسكرية في البحر الأحمر', 'region' => 'السعودية', 'actor' => 'التحالف الدولي', 'type' => 'عسكري/أمني', 'score' => 150],
            ['title' => 'تسريبات حول مفاوضات وقف إطلاق النار', 'region' => 'الأراضي المحتلة (إسرائيل)', 'actor' => 'وساطة دولية', 'type' => 'سياسي', 'score' => 170],
            ['title' => 'هجوم إلكتروني على البنية التحتية', 'region' => 'لبنان', 'actor' => 'جهة مجهولة', 'type' => 'إلكتروني', 'score' => 140],
            ['title' => 'تحرك دبلوماسي فرنسي في المنطقة', 'region' => 'لبنان', 'actor' => 'فرنسا', 'type' => 'سياسي', 'score' => 100],
            ['title' => 'ارتفاع التوتر في الجولان المحتل', 'region' => 'سوريا', 'actor' => 'إسرائيل', 'type' => 'عسكري/أمني', 'score' => 160],
            ['title' => 'تصريح أمريكي حول الوضع الإقليمي', 'region' => 'الولايات المتحدة', 'actor' => 'الولايات المتحدة', 'type' => 'سياسي', 'score' => 110],
        ];
        
        foreach ($test_events as $event) {
            $wpdb->insert($table, [
                'hash_id' => md5(uniqid($event['title'], true)),
                'title' => $event['title'],
                'war_data' => 'بيانات تجريبية للاختبار فقط',
                'link' => 'https://example.com',
                'source_name' => 'مصدر تجريبي',
                'score' => $event['score'],
                'region' => $event['region'],
                'actor_v2' => $event['actor'],
                'intel_type' => $event['type'],
                'event_timestamp' => time() - rand(0, 72 * 3600),
                'tactical_level' => 'operational',
                'llm_verified' => 1,
                'status' => 'published',
                'field_data' => json_encode([
                    'attribution' => ['status' => 'confirmed'],
                    'multi_actor' => ['target' => '', 'context_actor' => '', 'intent' => 'ردع']
                ])
            ]);
        }
        
        echo "<div style='background:#efe;border:1px solid:#cfc;padding:15px;border-radius:8px;margin-top:15px;'>";
        echo "<h3>✅ تم إضافة 10 أحداث تجريبية بنجاح!</h3>";
        echo "<p><a href='?refresh' style='color:#16a34a;font-weight:bold;'>↻ تحديث الصفحة</a></p>";
        echo "</div>";
    }
} else {
    echo "<div style='background:#efe;border:1px solid:#cfc;padding:15px;border-radius:8px;'>";
    echo "<h3>✅ توجد بيانات في قاعدة البيانات</h3>";
    echo "<p>يمكنك الآن زيارة صفحة <a href='" . admin_url('admin.php?page=strategic-osint-reports') . "' style='color:#16a34a;font-weight:bold;'>التقارير التنفيذية</a> في لوحة التحكم.</p>";
    echo "</div>";
    
    // عرض عينة من الأحداث
    echo "<h3>آخر 5 أحداث:</h3>";
    $events = $wpdb->get_results("SELECT title, region, score, event_timestamp FROM {$table} WHERE status='published' ORDER BY event_timestamp DESC LIMIT 5", ARRAY_A);
    echo "<ul>";
    foreach ($events as $e) {
        echo "<li><strong>[{$e['score']}]</strong> {$e['title']} — {$e['region']} (" . date('Y-m-d H:i', $e['event_timestamp']) . ")</li>";
    }
    echo "</ul>";
}

// اختبار بناء التقرير
if ($count > 0 && class_exists('SO_Executive_Reports')) {
    echo "<hr><h3>🧪 اختبار بناء التقرير:</h3>";
    try {
        $report = SO_Executive_Reports::build_report();
        echo "<pre style='background:#f5f5f5;padding:15px;border-radius:6px;overflow:auto;max-height:400px;'>";
        echo esc_html(print_r($report, true));
        echo "</pre>";
    } catch (Exception $e) {
        echo "<div style='background:#fee;border:1px solid:#fcc;padding:15px;border-radius:6px;'>";
        echo "<strong>❌ خطأ في بناء التقرير:</strong><br>";
        echo esc_html($e->getMessage());
        echo "</div>";
    }
}
?>
