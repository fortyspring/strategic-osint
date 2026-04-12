<?php
/**
 * Safe Executive Reports Module
 * استبدال آمن لوحدة التقارير لتجنب الأخطاء الحرجة
 * يقوم بعرض التقارير حتى في حال عدم وجود بيانات أو وجود أخطاء في الحسابات
 */

if (!defined('ABSPATH')) exit;

class SO_Safe_Reports {

    public static function render_executive_report() {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';

        // 1. فحص وجود الجدول
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            echo '<div class="notice notice-error"><p>⚠️ جدول الأحداث غير موجود. يرجى تشغيل أداة الإصلاح أولاً.</p></div>';
            return;
        }

        // 2. جلب البيانات بأمان (مع حماية من الأخطاء)
        try {
            $total_events = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            
            // جلب آخر 5 أحداث
            $recent_events = $wpdb->get_results("SELECT * FROM $table ORDER BY event_date DESC LIMIT 5", ARRAY_A);
            
            // حسابات آمنة للطبقات (مع قيم افتراضية)
            $field_action = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE layer = 'field_action'");
            $statement = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE layer = 'statement'");
            $report = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE layer = 'report'");
            $defensive_alert = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE layer = 'defensive_alert'");
            $rumor = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE layer = 'rumor'");
            $general = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE layer = 'general'");

            // حسابات آمنة لاتجاهات الحرب
            $escalating = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE war_direction = 'escalating'");
            $de_escalating = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE war_direction = 'de-escalating'");
            $aftermath = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE war_direction = 'aftermath'");
            $stable = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE war_direction = 'stable_or_unclear'");

            // حساب مؤشر التصعيد (مع تجنب القسمة على صفر)
            $total_directions = $escalating + $de_escalating + $aftermath + $stable;
            $escalation_index = ($total_directions > 0) ? round(($escalating / $total_directions) * 100) : 0;

        } catch (Exception $e) {
            // في حال حدوث أي خطأ في الاستعلام، نعرض قيماً صفرية بدلاً من توقف الموقع
            $total_events = 0;
            $recent_events = [];
            $field_action = $statement = $report = $defensive_alert = $rumor = $general = 0;
            $escalating = $de_escalating = $aftermath = $stable = 0;
            $escalation_index = 0;
            echo '<div class="notice notice-warning"><p>⚠️ تم تفعيل وضع السلامة: حدثت مشكلة في جلب بعض الإحصائيات، لكن الصفحة تعمل.</p></div>';
        }

        // 3. عرض التقرير (HTML آمن)
        ?>
        <div class="wrap">
            <h1>📊 التقارير التنفيذية الاستراتيجية</h1>
            
            <!-- بطاقات الملخص العلوية -->
            <div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-right: 4px solid #2271b1;">
                    <h3 style="margin: 0; color: #666; font-size: 14px;">إجمالي الأحداث</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo number_format($total_events); ?></p>
                </div>
                
                <div style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-right: 4px solid <?php echo ($escalation_index > 50) ? '#d63638' : '#00a32a'; ?>;">
                    <h3 style="margin: 0; color: #666; font-size: 14px;">مؤشر التصعيد</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo $escalation_index; ?>%</p>
                    <small><?php echo ($escalation_index > 50) ? '🔴 مرتفع' : '🟢 مستقر'; ?></small>
                </div>

                <div style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-right: 4px solid #f0b849;">
                    <h3 style="margin: 0; color: #666; font-size: 14px;">أحداث ميدانية</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo number_format($field_action); ?></p>
                </div>
            </div>

            <!-- قسم طبقات الحقيقة -->
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h2>🛡️ توزيع طبقات الحقيقة</h2>
                <table class="wp-list-table widefat fixed striped" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>الطبقة</th>
                            <th>العدد</th>
                            <th>النسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $layers = [
                            'field_action' => ['label' => 'إجراء ميداني', 'count' => $field_action],
                            'statement' => ['label' => 'بيان رسمي', 'count' => $statement],
                            'report' => ['label' => 'تقرير إعلامي', 'count' => $report],
                            'defensive_alert' => ['label' => 'إنذار دفاعي', 'count' => $defensive_alert],
                            'rumor' => ['label' => 'إشاعة', 'count' => $rumor],
                            'general' => ['label' => 'عام', 'count' => $general],
                        ];
                        foreach ($layers as $key => $data): 
                            $percent = ($total_events > 0) ? round(($data['count'] / $total_events) * 100) : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo $data['label']; ?></strong></td>
                            <td><?php echo $data['count']; ?></td>
                            <td>
                                <div style="background: #eee; height: 10px; border-radius: 5px; width: 100%; max-width: 200px;">
                                    <div style="background: #2271b1; height: 100%; border-radius: 5px; width: <?php echo $percent; ?>%;"></div>
                                </div>
                                <small><?php echo $percent; ?>%</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- قسم اتجاهات الحرب -->
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h2>⚔️ اتجاهات ساحة الحرب</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div style="text-align: center; padding: 15px; background: #ffebeb; border-radius: 5px;">
                        <h4>🔴 تصعيدي</h4>
                        <p style="font-size: 24px; font-weight: bold;"><?php echo $escalating; ?></p>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #e6ffed; border-radius: 5px;">
                        <h4>🟢 هدوء/تهدئة</h4>
                        <p style="font-size: 24px; font-weight: bold;"><?php echo $de_escalating; ?></p>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #fff8e5; border-radius: 5px;">
                        <h4>🟡 ما بعد الحدث</h4>
                        <p style="font-size: 24px; font-weight: bold;"><?php echo $aftermath; ?></p>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #f0f0f1; border-radius: 5px;">
                        <h4>⚪ مستقر/غير واضح</h4>
                        <p style="font-size: 24px; font-weight: bold;"><?php echo $stable; ?></p>
                    </div>
                </div>
            </div>

            <!-- آخر الأحداث -->
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h2>🕒 آخر الأحداث المسجلة</h2>
                <?php if (empty($recent_events)): ?>
                    <p style="color: #666; text-align: center; padding: 20px;">لا توجد أحداث مسجلة حتى الآن.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>العنوان</th>
                                <th>الطبقة</th>
                                <th>الاتجاه</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_events as $event): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i', strtotime($event['event_date'])); ?></td>
                                <td><?php echo esc_html(wp_trim_words($event['title'], 10)); ?></td>
                                <td><span style="background: #eee; padding: 2px 8px; border-radius: 4px; font-size: 12px;"><?php echo esc_html($event['layer']); ?></span></td>
                                <td><span style="background: #eee; padding: 2px 8px; border-radius: 4px; font-size: 12px;"><?php echo esc_html($event['war_direction']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
