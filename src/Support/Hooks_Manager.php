<?php
namespace LunexiaLiveFeedback\Support;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hook manager for registering and managing WordPress hooks
 */
class Hooks_Manager {
    protected static $hooks = [];

    /**
     * Register an action
     */
    public static function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        add_action( $hook, $callback, $priority, $accepted_args );
        self::$hooks[] = [ 'type' => 'action', 'hook' => $hook, 'callback' => $callback ];
    }

    /**
     * Register a filter
     */
    public static function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        add_filter( $hook, $callback, $priority, $accepted_args );
        self::$hooks[] = [ 'type' => 'filter', 'hook' => $hook, 'callback' => $callback ];
    }

    /**
     * Get all registered hooks
     */
    public static function get_hooks() {
        return self::$hooks;
    }

    /**
     * Clear all hooks
     */
    public static function clear_hooks() {
        self::$hooks = [];
    }
}
