<?php
/**
 * Validation Utilities Class
 * 
 * Provides validation functions for data integrity.
 * 
 * @package Beiruttime\OSINT\Utils
 */

namespace Beiruttime\OSINT\Utils;

class Validation {
    /**
     * Validate email address
     * 
     * @param string $email The email to validate
     * @return bool True if valid
     */
    public static function isEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     * 
     * @param string $url The URL to validate
     * @return bool True if valid
     */
    public static function isUrl(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate integer
     * 
     * @param mixed $value The value to validate
     * @param int|null $min Minimum value (optional)
     * @param int|null $max Maximum value (optional)
     * @return bool True if valid
     */
    public static function isInteger($value, ?int $min = null, ?int $max = null): bool {
        if (!is_numeric($value) || intval($value) != $value) {
            return false;
        }

        $intValue = (int)$value;

        if ($min !== null && $intValue < $min) {
            return false;
        }

        if ($max !== null && $intValue > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validate string length
     * 
     * @param string $string The string to validate
     * @param int $minLength Minimum length
     * @param int|null $maxLength Maximum length (optional)
     * @return bool True if valid
     */
    public static function isStringLength(string $string, int $minLength, ?int $maxLength = null): bool {
        $length = mb_strlen($string);

        if ($length < $minLength) {
            return false;
        }

        if ($maxLength !== null && $length > $maxLength) {
            return false;
        }

        return true;
    }

    /**
     * Validate array contains required keys
     * 
     * @param array $array The array to validate
     * @param array $requiredKeys Required keys
     * @return bool True if all required keys exist
     */
    public static function hasRequiredKeys(array $array, array $requiredKeys): bool {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $array)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate value in enum
     * 
     * @param mixed $value The value to validate
     * @param array $allowedValues Allowed values
     * @param bool $strict Strict comparison
     * @return bool True if value is allowed
     */
    public static function isInEnum($value, array $allowedValues, bool $strict = true): bool {
        if ($strict) {
            return in_array($value, $allowedValues, true);
        }
        return in_array($value, $allowedValues, false);
    }

    /**
     * Validate date format
     * 
     * @param string $date The date string
     * @param string $format Expected format (default: Y-m-d)
     * @return bool True if valid
     */
    public static function isDateFormat(string $date, string $format = 'Y-m-d'): bool {
        $dateTime = \DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    /**
     * Validate Arabic text
     * 
     * @param string $text The text to validate
     * @param int $minLength Minimum length
     * @return bool True if valid Arabic text
     */
    public static function isArabicText(string $text, int $minLength = 1): bool {
        if (mb_strlen($text) < $minLength) {
            return false;
        }

        return preg_match('/^[\x{0600}-\x{06FF}\s]+$/u', $text) === 1;
    }

    /**
     * Sanitize and validate input
     * 
     * @param mixed $input The input to sanitize
     * @param string $type Expected type (string, int, float, bool, array)
     * @return mixed Sanitized input or null if invalid
     */
    public static function sanitizeInput($input, string $type = 'string') {
        if ($input === null) {
            return null;
        }

        switch ($type) {
            case 'string':
                return is_scalar($input) ? TextUtils::sanitize((string)$input) : null;
            
            case 'int':
            case 'integer':
                return self::isInteger($input) ? (int)$input : null;
            
            case 'float':
                return is_numeric($input) ? (float)$input : null;
            
            case 'bool':
            case 'boolean':
                return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            
            case 'array':
                return is_array($input) ? $input : null;
            
            default:
                return null;
        }
    }

    /**
     * Validate non-empty string
     * 
     * @param mixed $value The value to validate
     * @return bool True if non-empty string
     */
    public static function isNonEmptyString($value): bool {
        return is_string($value) && trim($value) !== '';
    }

    /**
     * Validate positive number
     * 
     * @param mixed $value The value to validate
     * @return bool True if positive number
     */
    public static function isPositiveNumber($value): bool {
        return is_numeric($value) && $value > 0;
    }
}
