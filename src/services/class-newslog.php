<?php
/**
 * Newslog Service
 * 
 * Manages news classification records, manual overrides, and field extraction.
 * 
 * @package Beiruttime\OSINT\Services
 */

namespace Beiruttime\OSINT\Services;

use Beiruttime\OSINT\Traits\Singleton;
use Beiruttime\OSINT\Traits\Loggable;

class Newslog {
    use Singleton, Loggable;

    /**
     * Classification Fields Structure
     */
    private array $fieldStructure = [
        'title' => ['label' => 'العنوان', 'type' => 'string', 'required' => true],
        'strategic_level' => ['label' => 'التصنيف الاستراتيجي', 'type' => 'enum', 'values' => ['عام', 'عسكري', 'أمني', 'سياسي', 'اقتصادي/لوجستي', 'إعلامي', 'الدبلوماسي والسياسي']],
        'tactical_level' => ['label' => 'التصنيف التكتيكي', 'type' => 'enum', 'values' => ['عملياتي', 'تكتيكي', 'ميداني', 'اعلامي']],
        'region' => ['label' => 'المنطقة', 'type' => 'string'],
        'actor' => ['label' => 'الفاعل', 'type' => 'string'],
        'target' => ['label' => 'الهدف', 'type' => 'string'],
        'context' => ['label' => 'السياق', 'type' => 'string'],
        'intent' => ['label' => 'النية', 'type' => 'enum', 'values' => ['هجوم', 'دفاع', 'جمع معلومات', 'تصريح', 'مفاوضات', 'تهديد', 'تدريب', 'تموضع/رسائل سياسية', 'نشاط ميداني', 'حرب نفسية']],
        'weapon' => ['label' => 'السلاح', 'type' => 'string'],
        'outcome' => ['label' => 'النتيجة', 'type' => 'string'],
        'source' => ['label' => 'المصدر', 'type' => 'string'],
        'points' => ['label' => 'النقاط', 'type' => 'integer'],
    ];

    /**
     * Manual Override Storage (Simulated Database)
     * In production, this would be stored in WordPress postmeta or a custom table
     */
    private array $manualOverrides = [];

    /**
     * Check if a row has manual lock
     * 
     * @param array $row The news item row
     * @return bool True if manually locked
     */
    public function isManualLockedRow(array $row): bool {
        // Check if the row has been manually overridden
        $rowId = $row['id'] ?? null;
        
        if (!$rowId) {
            return false;
        }

        // Check in-memory overrides
        if (isset($this->manualOverrides[$rowId])) {
            return true;
        }

        // Check for WordPress meta if available
        if (function_exists('get_post_meta')) {
            $locked = get_post_meta($rowId, '_bt_osint_manual_lock', true);
            return !empty($locked);
        }

        return false;
    }

    /**
     * Extract Classification Fields from Row
     * 
     * Parses the structured data from a news item row.
     * 
     * @param array $row The news item row
     * @return array Extracted fields
     */
    public function extractClassificationFields(array $row): array {
        $fields = [];

        foreach ($this->fieldStructure as $fieldKey => $structure) {
            $value = null;

            // Try to get from direct property
            if (isset($row[$fieldKey])) {
                $value = $row[$fieldKey];
            }
            
            // Try to get from meta/array format
            if (isset($row['meta'][$fieldKey])) {
                $value = $row['meta'][$fieldKey];
            }

            // Validate against structure
            if ($value !== null && $structure['type'] === 'enum') {
                if (!in_array($value, $structure['values'])) {
                    $this->log("Invalid value for {$fieldKey}: {$value}", 'warning');
                    $value = null;
                }
            }

            $fields[$fieldKey] = $value;
        }

        // Special handling for compound fields
        if (!empty($row['classification_text'])) {
            $parsed = $this->parseClassificationText($row['classification_text']);
            $fields = array_merge($fields, $parsed);
        }

        return $fields;
    }

    /**
     * Parse Classification Text
     * 
     * Extracts structured data from unstructured classification text.
     * Example: "استراتيجي: عام\nتكتيكي: عملياتي\nمنطقة: إيران"
     * 
     * @param string $text The classification text
     * @return array Parsed fields
     */
    private function parseClassificationText(string $text): array {
        $result = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, ':') === false) {
                continue;
            }

            list($key, $value) = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Map common keys
            switch ($key) {
                case 'استراتيجي':
                    $result['strategic_level'] = $value;
                    break;
                case 'تكتيكي':
                    $result['tactical_level'] = $value;
                    break;
                case 'منطقة':
                    $result['region'] = $value;
                    break;
                case 'فاعل':
                    $result['actor'] = $value;
                    break;
                case 'هدف':
                    $result['target'] = $value;
                    break;
                case 'سياق':
                    $result['context'] = $value;
                    break;
                case 'نية':
                    $result['intent'] = $value;
                    break;
                case 'سلاح':
                    $result['weapon'] = $value;
                    break;
            }
        }

        return $result;
    }

    /**
     * Apply Manual Override
     * 
     * Allows manual correction of classification fields.
     * 
     * @param int $rowId The row ID
     * @param array $fields The corrected fields
     * @return bool Success status
     */
    public function applyManualOverride(int $rowId, array $fields): bool {
        // Store in memory
        $this->manualOverrides[$rowId] = [
            'fields' => $fields,
            'timestamp' => time(),
            'user_id' => function_exists('get_current_user_id') ? get_current_user_id() : 0,
        ];

        // Store in WordPress if available
        if (function_exists('update_post_meta')) {
            update_post_meta($rowId, '_bt_osint_manual_lock', '1');
            update_post_meta($rowId, '_bt_osint_manual_fields', $fields);
            update_post_meta($rowId, '_bt_osint_manual_timestamp', time());
        }

        $this->log("Manual override applied to row {$rowId}", 'info');
        return true;
    }

    /**
     * Get Manual Override Data
     * 
     * @param int $rowId The row ID
     * @return array|null Override data or null
     */
    public function getManualOverride(int $rowId): ?array {
        // Check memory first
        if (isset($this->manualOverrides[$rowId])) {
            return $this->manualOverrides[$rowId];
        }

        // Check WordPress meta
        if (function_exists('get_post_meta')) {
            $fields = get_post_meta($rowId, '_bt_osint_manual_fields', true);
            if (!empty($fields)) {
                return [
                    'fields' => $fields,
                    'timestamp' => get_post_meta($rowId, '_bt_osint_manual_timestamp', true),
                    'user_id' => get_post_meta($rowId, '_bt_osint_manual_user', true),
                ];
            }
        }

        return null;
    }

    /**
     * Remove Manual Override
     * 
     * @param int $rowId The row ID
     * @return bool Success status
     */
    public function removeManualOverride(int $rowId): bool {
        unset($this->manualOverrides[$rowId]);

        if (function_exists('delete_post_meta')) {
            delete_post_meta($rowId, '_bt_osint_manual_lock');
            delete_post_meta($rowId, '_bt_osint_manual_fields');
            delete_post_meta($rowId, '_bt_osint_manual_timestamp');
        }

        $this->log("Manual override removed from row {$rowId}", 'info');
        return true;
    }

    /**
     * Validate Classification Data
     * 
     * Ensures all required fields are present and valid.
     * 
     * @param array $fields The fields to validate
     * @return array Validation result with errors
     */
    public function validateClassificationData(array $fields): array {
        $errors = [];
        $warnings = [];

        foreach ($this->fieldStructure as $fieldKey => $structure) {
            $value = $fields[$fieldKey] ?? null;

            // Check required fields
            if ($structure['required'] && empty($value)) {
                $errors[] = "{$structure['label']} مطلوب";
                continue;
            }

            // Check enum values
            if ($structure['type'] === 'enum' && !empty($value)) {
                if (!in_array($value, $structure['values'])) {
                    $warnings[] = "{$structure['label']} يحتوي على قيمة غير معتادة: {$value}";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get Field Structure
     * 
     * @return array The field structure definition
     */
    public function getFieldStructure(): array {
        return $this->fieldStructure;
    }

    /**
     * Build Display String for Classification
     * 
     * Creates a human-readable string from classification fields.
     * 
     * @param array $fields The classification fields
     * @return string Formatted display string
     */
    public function buildDisplayString(array $fields): string {
        $parts = [];

        if (!empty($fields['strategic_level'])) {
            $parts[] = "استراتيجي: {$fields['strategic_level']}";
        }

        if (!empty($fields['tactical_level'])) {
            $parts[] = "تكتيكي: {$fields['tactical_level']}";
        }

        if (!empty($fields['region'])) {
            $parts[] = "منطقة: {$fields['region']}";
        }

        if (!empty($fields['actor'])) {
            $parts[] = "فاعل: {$fields['actor']}";
        }

        if (!empty($fields['target'])) {
            $parts[] = "هدف: {$fields['target']}";
        }

        if (!empty($fields['context'])) {
            $parts[] = "سياق: {$fields['context']}";
        }

        if (!empty($fields['intent'])) {
            $parts[] = "نية: {$fields['intent']}";
        }

        if (!empty($fields['weapon'])) {
            $parts[] = "سلاح: {$fields['weapon']}";
        }

        return implode("\n", $parts);
    }

    /**
     * Compare Classifications
     * 
     * Compares two classification sets and returns differences.
     * 
     * @param array $old Old classification
     * @param array $new New classification
     * @return array Differences
     */
    public function compareClassifications(array $old, array $new): array {
        $differences = [];

        foreach ($this->fieldStructure as $fieldKey => $structure) {
            $oldValue = $old[$fieldKey] ?? null;
            $newValue = $new[$fieldKey] ?? null;

            if ($oldValue !== $newValue) {
                $differences[$fieldKey] = [
                    'label' => $structure['label'],
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $differences;
    }
}
