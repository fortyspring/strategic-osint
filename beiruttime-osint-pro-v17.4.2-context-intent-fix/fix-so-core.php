<?php
/**
 * Strategic OSINT Emergency Fix Script
 * Run this file once via browser to fix DB tables and clear corrupted data.
 * URL: /wp-content/plugins/beiruttime-osint-pro-v17.4.2-context-intent-fix/fix-so-core.php
 */

// Load WordPress environment safely
require_once(__DIR__ . '/../../wp-load.php');

// Ensure only admin can run this
if (!current_user_can('manage_options')) {
    wp_die('Access Denied. Admin privileges required.');
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();
$table_prefix = $wpdb->prefix;

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>OSINT Emergency Repair</title>";
echo "<style>body{font-family:Cairo,sans-serif;direction:rtl;text-align:right;padding:40px;background:#f5f5f5;}h1{color:#c00;}p{background:#fff;padding:15px;border-radius:8px;margin:10px 0;box-shadow:0 2px 5px rgba(0,0,0,0.1);}.success{color:green;}.warning{color:orange;}.error{color:red;}</style></head><body>";
echo "<h1>🔧 إصلاح طوارئ - Beiruttime OSINT Pro</h1>";

// 1. Define Core Tables Structure
$tables = [
    "so_news_events" => "CREATE TABLE IF NOT EXISTS {$table_prefix}so_news_events (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        hash_id varchar(64) NOT NULL,
        title text NOT NULL,
        link varchar(500) NOT NULL,
        source_name varchar(100) NOT NULL,
        source_color varchar(20),
        intel_type varchar(100),
        tactical_level varchar(50) DEFAULT 'تكتيكي',
        war_data text NULL,
        region varchar(100),
        agency_loc varchar(100),
        score int(11) DEFAULT 0,
        event_timestamp bigint(20) NOT NULL,
        status varchar(20) DEFAULT 'published',
        image_url varchar(500),
        actor_v2 varchar(100),
        weapon_v2 varchar(100),
        target_v2 varchar(100),
        sentiment_score float DEFAULT 0,
        field_data text NULL,
        llm_verified tinyint(1) DEFAULT 0,
        title_fingerprint varchar(64) DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY hash_id (hash_id),
        KEY event_timestamp (event_timestamp),
        KEY region_time (region, event_timestamp),
        KEY title_fp (title_fingerprint),
        FULLTEXT KEY idx_search (title, war_data)
    ) $charset_collate;",

    "so_sent_alerts" => "CREATE TABLE IF NOT EXISTS {$table_prefix}so_sent_alerts (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        news_hash varchar(64) NOT NULL,
        source_name varchar(255) NOT NULL,
        sent_time datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY news_hash (news_hash)
    ) $charset_collate;",

    "so_manual_learning" => "CREATE TABLE IF NOT EXISTS {$table_prefix}so_manual_learning (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        title_fingerprint varchar(64) NOT NULL,
        source_name varchar(100) NOT NULL DEFAULT '',
        corrected_title text NULL,
        corrected_actor varchar(150) NULL,
        corrected_region varchar(150) NULL,
        corrected_intel_type varchar(150) NULL,
        corrected_level varchar(100) NULL,
        corrected_score int(11) DEFAULT NULL,
        corrected_weapon varchar(150) NULL,
        corrected_target varchar(150) NULL,
        corrected_war_data longtext NULL,
        learn_weight int(11) NOT NULL DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_rule (title_fingerprint, source_name)
    ) $charset_collate;"
];

// 2. Create/Repair Tables
foreach ($tables as $table_name => $sql) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    echo "<p class='success'>✓ جدول <strong>$table_name</strong> تم التحقق منه/إنشاؤه.</p>";
}

// 3. Clean Corrupted Data (Fix Double Encoding & Duplicates)
echo "<h3>تنظيف البيانات الفاسدة...</h3>";

// Fix double-encoded Arabic in options (learning banks)
$options_to_fix = [
    'sod_bank_actors',
    'sod_bank_targets', 
    'sod_bank_contexts',
    'sod_bank_intents',
    'sod_bank_weapons',
    'sod_bank_types',
    'sod_bank_levels',
    'sod_bank_regions',
    'sod_visible_learning_banks'
];

$fixed_count = 0;
foreach ($options_to_fix as $opt_name) {
    $current = get_option($opt_name, []);
    if (!is_array($current) || empty($current)) continue;
    
    $needs_update = false;
    $new_values = [];
    
    foreach ($current as $key => $value) {
        $term = is_array($value) ? ($value['term'] ?? '') : (string)$value;
        
        // Check for mojibake (double encoding)
        if (preg_match('/[ØÙ][0-9A-F]{2}/u', $term) || preg_match('/Ã|Å/u', $term)) {
            $decoded = @utf8_decode($term);
            if ($decoded && preg_match('/[\x{0600}-\x{06FF}]/u', $decoded)) {
                if (is_array($value)) {
                    $value['term'] = $decoded;
                } else {
                    $value = $decoded;
                }
                $needs_update = true;
                $fixed_count++;
            }
        }
        $new_values[$key] = $value;
    }
    
    if ($needs_update) {
        update_option($opt_name, $new_values, false);
        echo "<p class='warning'>• تم إصلاح ترميز خيار <strong>$opt_name</strong> ($fixed_count قيمة).</p>";
    }
}

// Remove exact duplicates in visible_learning_banks
$saved = get_option('sod_visible_learning_banks', []);
if (is_array($saved)) {
    foreach ($saved as $bank_type => $values) {
        if (is_array($values)) {
            $unique = array_values(array_unique($values));
            if (count($unique) !== count($values)) {
                $saved[$bank_type] = $unique;
                echo "<p class='warning'>• تمت إزالة التكرارات من بنك <strong>$bank_type</strong>.</p>";
            }
        }
    }
    update_option('sod_visible_learning_banks', $saved, false);
}

echo "<p class='success'>✓ اكتمل تنظيف البيانات الفاسدة.</p>";

// 4. Reset Learning Banks to Defaults if Completely Broken
echo "<h3>إعادة تعيين بنوك المعلومات للقيم الافتراضية...</h3>";

$defaults = [
    'actors' => ['المقاومة الإسلامية (حزب الله)', 'جيش العدو الإسرائيلي', 'الولايات المتحدة', 'إيران', 'حماس', 'الجهاد الإسلامي', 'الحرس الثوري الإيراني', 'الجيش اللبناني', 'أنصار الله (الحوثيون)', 'الحشد الشعبي'],
    'regions' => ['لبنان', 'فلسطين', 'سوريا', 'العراق', 'اليمن', 'إيران', 'الأراضي المحتلة (إسرائيل)', 'الخليج', 'الولايات المتحدة'],
    'types' => ['عسكري/أمني', 'سياسي', 'إعلامي', 'اقتصادي/لوجستي', 'الدبلوماسي والسياسي', 'الإعلامي والمعلوماتي'],
    'levels' => ['استراتيجي', 'عملياتي', 'تكتيكي'],
    'contexts' => ['رد على اعتداء', 'تصعيد متبادل', 'بيان سياسي', 'عمل استباقي', 'رسالة ردع'],
    'intents' => ['هجوم', 'دفاع', 'ردع', 'استطلاع', 'تحذير'],
    'weapons' => ['طائرات مسيرة', 'صاروخ باليستي', 'صاروخ كروز', 'مدفعية', 'دبابات / آليات'],
    'targets' => ['قواعد عسكرية', 'مناطق سكنية', 'بنية تحتية', 'مطارات', 'موانئ'],
];

foreach ($defaults as $type => $values) {
    $opt_name = 'sod_bank_' . $type;
    $current = get_option($opt_name, []);
    
    // If empty or corrupted, reset to defaults
    if (empty($current) || !is_array($current)) {
        update_option($opt_name, $values, false);
        echo "<p class='success'>• تم تعيين القيم الافتراضية لبنك <strong>$type</strong>.</p>";
    }
}

// Also update sod_visible_learning_banks with clean defaults
$current_visible = get_option('sod_visible_learning_banks', []);
if (empty($current_visible) || !is_array($current_visible)) {
    update_option('sod_visible_learning_banks', $defaults, false);
    echo "<p class='success'>✓ تم إعادة تعيين جميع بنوك المعلومات للقيم الافتراضية النظيفة.</p>";
}

// 5. Clear any transient cache
delete_transient('sod_dashboard_cache');
delete_transient('sod_newslog_cache');
echo "<p class='success'>✓ تم مسح ذاكرة التخزين المؤقت.</p>";

echo "<hr><h2 style='color:green;'>✅ اكتمل الإصلاح!</h2>";
echo "<div style='background:#e8f5e9;padding:20px;border-radius:8px;margin-top:20px;'>";
echo "<h3>الخطوات التالية:</h3>";
echo "<ol>";
echo "<li>احذف هذا الملف (<code>fix-so-core.php</code>) من الخادم لأسباب أمنية.</li>";
echo "<li>انتقل إلى لوحة تحكم ووردبريس.</li>";
echo "<li>قم بتحديث الصفحة بقوة (Ctrl+F5 أو Cmd+Shift+R).</li>";
echo "<li>إذا استمر الخطأ الحرج، تحقق من ملف <code>wp-content/debug.log</code> لمعرفة الخطأ الدقيق.</li>";
echo "</ol>";
echo "</div>";

echo "<p style='margin-top:30px;'><a href='" . admin_url('admin.php?page=strategic-osint') . "' style='display:inline-block;padding:15px 30px;background:#0073aa;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;'>← العودة إلى لوحة OSINT</a></p>";

echo "</body></html>";
