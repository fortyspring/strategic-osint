<?php
/**
 * Loggable Trait
 * 
 * Provides logging functionality to classes that use it.
 * Integrates with WordPress debug log or standard error log.
 * 
 * @package Beiruttime\OSINT\Traits
 */

namespace Beiruttime\OSINT\Traits;

trait Loggable {
    /**
     * Log a message
     * 
     * @param string $message The message to log
     * @param string $level The log level (info, warning, error, debug)
     * @return void
     */
    protected function log(string $message, string $level = 'info'): void {
        $timestamp = current_time('mysql');
        $class = static::class;
        
        $logMessage = sprintf(
            "[%s] [%s] [%s] %s",
            $timestamp,
            strtoupper($level),
            $class,
            $message
        );

        // Use WordPress debug log if available
        if (function_exists('error_log')) {
            error_log($logMessage);
        } else {
            // Fallback to standard error log
            error_log($logMessage);
        }

        // Also log to WP Debug if enabled
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG')) {
            // WordPress will automatically log via error_log
        }
    }

    /**
     * Log an info message
     * 
     * @param string $message The message to log
     * @return void
     */
    protected function logInfo(string $message): void {
        $this->log($message, 'info');
    }

    /**
     * Log a warning message
     * 
     * @param string $message The message to log
     * @return void
     */
    protected function logWarning(string $message): void {
        $this->log($message, 'warning');
    }

    /**
     * Log an error message
     * 
     * @param string $message The message to log
     * @return void
     */
    protected function logError(string $message): void {
        $this->log($message, 'error');
    }

    /**
     * Log a debug message
     * 
     * @param string $message The message to log
     * @return void
     */
    protected function logDebug(string $message): void {
        $this->log($message, 'debug');
    }
}
