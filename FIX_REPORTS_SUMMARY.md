# إصلاح قسم التقارير التنفيذية - Beiruttime OSINT

## المشكلة
القسم يفتح ولكن لا يظهر أي محتوى داخله.

## التشخيص
بعد فحص الكود، تم العثور على المكونات التالية:

### 1. الصفحة موجودة وتعمل
- **الدالة:** `page_reports()` (السطر 8551)
- **المسار:** `admin.php?page=strategic-osint-reports`
- **العنوان:** "التقارير التنفيذية"

### 2. المكونات الأساسية موجودة
- ✅ فئة `SO_Executive_Reports` (السطر 4213)
- ✅ دالة `build_report()` (السطر 4242) - تبني التقرير
- ✅ دالة `render_exec_report_html()` (السطر 4533) - تعرض التقرير كـ HTML
- ✅ AJAX للإرسال الفوري (السطر 4767)
- ✅ جدول البيانات موجود: `wp_so_news_events`

### 3. الأسباب المحتملة للمشكلة

#### السبب الأرجح: لا توجد بيانات في قاعدة البيانات
دالة `build_report()` ترجع رسالة فارغة إذا لم تجد أحداثاً:
```php
if (empty($events)) {
    return ['text' => "🧭 التقدير الاستخباراتي — Beiruttime OSINT\n\nلا توجد أحداث منشورة كافية خلال آخر {$window_hours} ساعة."];
}
```

#### أسباب أخرى محتملة:
1. **خطأ PHP صامت** - قد يكون هناك خطأ يمنع العرض
2. **مشكلة في الـ Nonce** - التحقق من الصلاحية يفشل
3. **CSS يخفي المحتوى** - مشكلة في التنسيق

## الحلول المقترحة

### الحل 1: إضافة بيانات تجريبية (موصى به أولاً)
استخدم ملف الاختبار المرفق `test_executive_report.php`:

1. انقل الملف إلى: `wp-content/test_executive_report.php`
2. افتح المتصفح: `your-site.com/wp-content/test_executive_report.php`
3. اضغط "إضافة 10 أحداث تجريبية"
4. عد إلى صفحة التقارير في لوحة التحكم

### الحل 2: تفعيل وضع التصحيح في ووردبريس
أضف إلى `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```
ثم تحقق من ملف `wp-content/debug.log`

### الحل 3: التحقق من سجل الأخطاء
```bash
# سجلات Nginx
sudo tail -f /var/log/nginx/error.log

# سجلات Apache
sudo tail -f /var/log/apache2/error.log

# سجلات ووردبريس
tail -f wp-content/debug.log
```

### الحل 4: فحص قاعدة البيانات يدوياً
```sql
-- التحقق من وجود الأحداث
SELECT COUNT(*) FROM wp_so_news_events WHERE status='published';

-- عرض آخر 5 أحداث
SELECT title, region, score, event_timestamp 
FROM wp_so_news_events 
WHERE status='published' 
ORDER BY event_timestamp DESC 
LIMIT 5;
```

## اختبار سريع من سطر الأوامر

إذا كان لديك وصول لـ WP-CLI:
```bash
# تقييم دالة بناء التقرير
wp eval 'global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}so_news_events WHERE status=\"published\"");'

# إذا كانت النتيجة 0، أضف بيانات تجريبية
```

## الملفات المتأثرة
- `/beiruttime-osint-pro-v17.4.2-context-intent-fix/beiruttime-osint-pro.php`
  - السطر 4213-4774: فئة `SO_Executive_Reports`
  - السطر 8551-8720: دالة `page_reports()`

## الخطوات التالية
1. ✅ تشغيل ملف الاختبار `test_executive_report.php`
2. 📊 إذا ظهرت البيانات، المشكلة كانت نقص البيانات
3. 🔍 إذا استمرت المشكلة، راجع سجلات الأخطاء
4. 🛠️ قدم لنا مخرجات ملف `debug.log` إذا وجدت أخطاء

---
**تاريخ الإصلاح:** 2025
**الحالة:** قيد التشخيص - بانتظار نتائج ملف الاختبار
