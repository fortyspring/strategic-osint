<?php
/**
 * Text Utilities Class
 * 
 * Provides text manipulation and normalization functions.
 * Especially useful for Arabic text processing.
 * 
 * @package Beiruttime\OSINT\Utils
 */

namespace Beiruttime\OSINT\Utils;

class TextUtils {
    /**
     * Normalize Arabic text
     * 
     * Standardizes Arabic characters for consistent matching.
     * - Converts alef variants to standard alef
     * - Converts yeh variants to standard yeh
     * - Removes tatweel (elongation)
     * - Normalizes whitespace
     * 
     * @param string $text The text to normalize
     * @return string Normalized text
     */
    public static function normalizeArabic(string $text): string {
        // Convert Arabic characters to their standard forms
        $search = [
            'أ', 'إ', 'آ', 'ٱ', // Alef variants
            'ى',               // Alif maqsura
            'ة',               // Ta marbuta
            'ـ',               // Tatweel (elongation)
            'ؤ', 'ئ',          // Hamza on waw/yeh
        ];
        
        $replace = [
            'ا', 'ا', 'ا', 'ا', // Standard alef
            'ي',               // Standard yeh
            'ه',               // Standard heh
            '',                // Remove tatweel
            'و', 'ي',          // Standard waw/yeh
        ];

        $normalized = str_replace($search, $replace, $text);

        // Normalize whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        return $normalized;
    }

    /**
     * Remove Arabic diacritics (Tashkeel)
     * 
     * @param string $text The text to process
     * @return string Text without diacritics
     */
    public static function removeDiacritics(string $text): string {
        $diacritics = [
            '\u{064B}', // Fathatan
            '\u{064C}', // Dammatan
            '\u{064D}', // Kasratan
            '\u{064E}', // Fatha
            '\u{064F}', // Damma
            '\u{0650}', // Kasra
            '\u{0651}', // Shadda
            '\u{0652}', // Sukun
        ];

        return preg_replace('/[' . implode('', $diacritics) . ']/u', '', $text);
    }

    /**
     * Extract numbers from text
     * 
     * @param string $text The text to extract from
     * @return array Array of found numbers
     */
    public static function extractNumbers(string $text): array {
        preg_match_all('/\d+/', $text, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Convert Persian/Urdu numbers to Arabic-Indic
     * 
     * @param string $text The text to convert
     * @return string Converted text
     */
    public static function persianToArabicNumbers(string $text): string {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        return str_replace($persian, $arabic, $text);
    }

    /**
     * Clean HTML tags from text
     * 
     * @param string $text The text to clean
     * @return string Cleaned text
     */
    public static function stripHtml(string $text): string {
        return strip_tags($text);
    }

    /**
     * Truncate text to a maximum length
     * 
     * @param string $text The text to truncate
     * @param int $maxLength Maximum length
     * @param string $suffix Suffix to add if truncated
     * @return string Truncated text
     */
    public static function truncate(string $text, int $maxLength, string $suffix = '...'): string {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength) . $suffix;
    }

    /**
     * Check if text contains Arabic characters
     * 
     * @param string $text The text to check
     * @return bool True if contains Arabic
     */
    public static function isArabic(string $text): bool {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text) === 1;
    }

    /**
     * Extract keywords from text
     * 
     * @param string $text The text to extract from
     * @param int $minLength Minimum keyword length
     * @return array Array of keywords
     */
    public static function extractKeywords(string $text, int $minLength = 3): array {
        // Remove common Arabic stop words
        $stopWords = [
            'في', 'من', 'على', 'إلى', 'عن', 'مع', 'أن', 'إن', 'ما', 'لا',
            'هذا', 'هذه', 'ذلك', 'تلك', 'التي', 'الذي', 'الذين', 'اللذان',
            'وهو', 'هي', 'هم', 'هن', 'نحن', 'أنا', 'أنت', 'أنتما', 'أنتم',
        ];

        // Split by spaces and punctuation
        $words = preg_split('/[\s,\.\!\?\:\;\(\)\[\]]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Filter words
        $keywords = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) >= $minLength && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }

    /**
     * Sanitize text for database storage
     * 
     * @param string $text The text to sanitize
     * @return string Sanitized text
     */
    public static function sanitize(string $text): string {
        // Remove null bytes
        $text = str_replace("\0", '', $text);
        
        // Remove control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        return trim($text);
    }
}
