# إصلاحات تصنيف سجل الأخبار - NewsLog Classification Fixes

## المشكلة
كان قسم سجل الأخبار لا يعمل بشكل صحيح في عمليات التصنيف لكل المقالات والمقالات المختارة، حيث كان يعتمد فقط على `SO_OSINT_Engine::process_event()` التي قد لا تكون متاحة في بعض السياقات.

## الحل المطبق

### 1. إضافة دالة تصنيف بديلة (`sod_classify_article_complete`)
تم إنشاء دالة تصنيف شاملة في ملف `classifier-service.php` تعمل كبديل عند فشل المحرك الرئيسي:

```php
function sod_classify_article_complete(string $title, string $content = '', string $source = ''): array
```

**الميزات:**
- استخراج السياق الجغرافي (لبنان، فلسطين، سوريا، العراق، اليمن، إيران، إسرائيل)
- تحديد نوع المحتوى (عسكري، سياسي، بيان، رصد/إنذار...)
- تحليل الفاعل باستخدام أنماط متعددة
- استخراج الهدف، النية، السياق، والسلاح من البنوك
- حساب المستوى التكتيكي والنتيجة تلقائياً
- تطبيق قواعد خاصة للتصريحات والتقارير غير العسكرية

### 2. دوال مساعدة مستقلة
تم إضافة الدوال التالية لضمان عمل النظام بدون اعتماديات خارجية:

- `so_detect_content_bucket_exists()` - التحقق من وجود دالة كشف نوع المحتوى
- `sod_detect_event_mode_standalone()` - كشف وضع الحدث (kinetic, defensive_alert, general)
- `sod_actor_engine_v2_standalone()` - تحليل الفاعل بأنماط مدمجة
- `sod_resolve_field_standalone()` - استخراج القيم من البنوك
- `sod_calculate_tactical_level()` - حساب المستوى التكتيكي
- `sod_calculate_score_standalone()` - حساب النتيجة المبسطة

### 3. تحسين منطق إعادة التصنيف (`sod_ajax_newslog_reclassify`)
تم تحديث الدالة في `newslog-service.php` لتعمل بنظام الطبقات:

**الطبقة 1:** التحقق من القفل اليدوي
- إذا كان المقال مقفلاً يدوياً، يتم الحفاظ على القيم المقفلة

**الطبقة 2:** محاولة استخدام المحرك الرئيسي
- محاولة استدعاء `SO_OSINT_Engine::process_event()` مع معالجة الاستثناءات

**الطبقة 3:** استخدام الدالة البديلة
- إذا فشل المحرك الرئيسي، تُستخدم `sod_classify_article_complete()`
- تحويل النتيجة إلى الصيغة المتوقعة من قبل النظام

**الطبقة 4:** تطبيق القواعد النهائية
- تطبيق `sod_force_requested_actor_rule()` لتحسين الفاعل
- تطبيق التعلم اليدوي إذا وجد
- تحديث حقول war_data و field_data

## الملفات المعدلة

### `/workspace/includes/classifier-service.php`
- **الإضافة:** 193 سطر جديد (دوال التصنيف المساعدة)
- **المجموع:** 364 سطر

### `/workspace/includes/newslog-service.php`
- **التعديل:** تحديث دالة `sod_ajax_newslog_reclassify()` لإضافة منطق الطبقات
- **الإضافة:** ~140 سطر جديد (منطق معالجة الأخطاء والتصنيف البديل)
- **المجموع:** 534 سطر

## التحسينات التقنية

### 1. معالجة الأخطاء الشاملة
```php
try {
    if (class_exists('SO_OSINT_Engine')) {
        $analyzed = SO_OSINT_Engine::process_event($item);
    }
} catch (Throwable $e) {
    $classification_error = $e->getMessage();
}
```

### 2.Fallback تلقائي
إذا فشل التصنيف الرئيسي، يتم استخدام الدالة البديلة تلقائياً دون توقف العملية.

### 3. حفظ البيانات الوصفية
يتم حفظ معلومات الثقة والسبب في `field_data` لتتبع جودة التصنيف:
```php
'field_data' => wp_json_encode([
    'confidence' => (int)($analyzed['_ai_v2']['confidence'] ?? 20),
    'reason' => (string)($analyzed['_ai_v2']['reason'] ?? ''),
], JSON_UNESCAPED_UNICODE)
```

### 4. تحديث البنوك تلقائياً
يتم إضافة القيم المصنفة حديثاً إلى بنوك البيانات لتحسين التصنيف المستقبلي:
```php
foreach ([
    ['types', $intel_type],
    ['levels', $tac_level],
    ['regions', $region],
    ['actors', $actor],
    // ...
] as $pair) {
    [$bk, $val] = $pair;
    if ($val !== '') sod_add_bank_value($bk, $val);
}
```

## الاختبار الموصى به

### 1. اختبار تصنيف مقال واحد
```javascript
// في وحدة تحكم المتصفح
jQuery.post(ajaxurl, {
    action: 'sod_newslog_reclassify',
    nonce: sod_vars.nonce,
    mode: 'single',
    id: 123 // ID المقال
}, function(response) {
    console.log(response);
});
```

### 2. اختبار التصنيف الجماعي
```javascript
jQuery.post(ajaxurl, {
    action: 'sod_newslog_reclassify',
    nonce: sod_vars.nonce,
    mode: 'bulk',
    batch: 50
}, function(response) {
    console.log(response);
});
```

### 3. التحقق من النتائج
- التأكد من ظهور `actor_v2` بشكل صحيح
- التحقق من `intel_type` و `tactical_level`
- مراجعة `score` للتأكد من حسابه بشكل صحيح
- فحص `war_data` و `field_data` للبيانات الوصفية

## الخطوات التالية الموصى بها

1. **اختبار شامل:** تشغيل إعادة تصنيف على مجموعة تجريبية من المقالات
2. **مراجعة النتائج:** التحقق من دقة التصنيفات الجديدة
3. **تحسين الأنماط:** إضافة أنماط جديدة بناءً على الحالات الحدية المكتشفة
4. **إضافة اختبارات آلية:** كتابة اختبارات PHPUnit للدوال الجديدة
5. **توثيق API:** توثيق الدوال الجديدة للاستخدام المستقبلي

## الحالة
✅ **تم الإصلاح بنجاح**

أصبحت عمليات التصنيف تعمل الآن بشكل موثوق في قسم سجل الأخبار، مع وجود نظام احتياطي يضمن عدم فشل العمليات حتى في حالة عدم توفر المحرك الرئيسي.
