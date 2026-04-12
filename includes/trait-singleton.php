<?php
/**
 * سمة Singleton للتوافق مع الملفات القديمة
 * 
 * @package StrategicOSINT
 */

if (!defined('ABSPATH')) exit;

/**
 * سمة Singleton للنمط الأحادي
 */
trait SOD_Singleton_Trait {
    
    /**
     * النسخة الوحيدة من الفئة
     * 
     * @var self|null
     */
    private static $instance = null;
    
    /**
     * الحصول على النسخة الوحيدة من الفئة
     * 
     * @return self
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * منع الاستنساخ
     */
    private function __clone() {}
    
    /**
     * منع إعادة التسلسل
     */
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton');
    }
}
