<?php
/**
 * Singleton Trait
 * 
 * Provides a simple implementation of the Singleton pattern.
 * Ensures only one instance of a class exists.
 * 
 * @package Beiruttime\OSINT\Traits
 */

namespace Beiruttime\OSINT\Traits;

trait Singleton {
    /**
     * Single instance of the class
     * 
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Get the singleton instance
     * 
     * @return static The singleton instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning of the singleton instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the singleton instance
     * 
     * @throws \Exception
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
