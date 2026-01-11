<?php
/**
 * Plugin Constants and Configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class LP_Constants {
    
    const PLUGIN_VERSION = '1.0.0';
    const TEXT_DOMAIN = 'loginpress-task';
    const OPTION_NAME = 'lp_task_settings';
    const MENU_SLUG = 'loginpress-task';
    
    // User meta keys
    const META_PASSWORD_HISTORY = '_lp_password_history';
    const META_LAST_UPDATE = '_lp_last_password_update';
    const META_LAST_REMINDER = '_lp_last_reminder_sent';
    
    // Cron hook
    const CRON_HOOK = 'lp_daily_password_reminder';
    
    // Default settings
    public static function get_default_settings() {
        return array(
            'force_password_reset' => false,
            'disallow_last_password' => false,
            'password_history_count' => 3,
            'enable_reminder' => false,
            'expiry_days' => 90,
            'reminder_days' => 7
        );
    }
}

