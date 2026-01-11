<?php
/**
 * Plugin Name: LoginPress Task Assessment
 * Plugin URI: https://example.com/loginpress-task
 * Description: A WordPress plugin that enforces password policies including password history and expiry reminders
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 * Text Domain: loginpress-task
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class - Refactored
 */
class LoginPress_Task_Assessment {
    
    /**
     * Plugin instances
     */
    private $admin;
    private $rest_api;
    private $validator;
    private $storage;
    private $email_service;
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->register_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once plugin_dir_path(__FILE__) . 'includes/class-lp-constants.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-lp-admin.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-lp-rest-api.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-lp-password-validator.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-lp-password-storage.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-lp-email-service.php';
    }
    
    /**
     * Initialize component instances
     */
    private function init_components() {
        $this->admin = new LP_Admin();
        $this->rest_api = new LP_REST_API();
        $this->validator = new LP_Password_Validator();
        $this->storage = new LP_Password_Storage();
        $this->email_service = new LP_Email_Service();
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Admin hooks
        add_action('admin_menu', array($this->admin, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_scripts'));
        
        // REST API hooks
        add_action('rest_api_init', array($this->rest_api, 'register_routes'));
        
        // Password validation hooks (Task 1)
        add_action('user_profile_update_errors', array($this->validator, 'validate_on_profile_update'), 10, 3);
        add_action('validate_password_reset', array($this->validator, 'validate_on_reset'), 10, 2);
        
        // Password storage hooks
        add_action('profile_update', array($this->storage, 'store_on_update'), 10, 2);
        add_action('user_register', array($this->storage, 'initialize_for_new_user'), 10);
        
        // Email reminder hooks (Task 2)
        add_action('init', array($this->email_service, 'schedule_cron'));
        add_action(LP_Constants::CRON_HOOK, array($this->email_service, 'send_reminders'));
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Initialize default settings
        if (!get_option(LP_Constants::OPTION_NAME)) {
            add_option(LP_Constants::OPTION_NAME, LP_Constants::get_default_settings());
        }
        
        // Schedule cron
        if (!wp_next_scheduled(LP_Constants::CRON_HOOK)) {
            wp_schedule_event(time(), 'daily', LP_Constants::CRON_HOOK);
        }
        
        error_log('LoginPress Task plugin activated');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron
        $timestamp = wp_next_scheduled(LP_Constants::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, LP_Constants::CRON_HOOK);
        }
        
        error_log('LoginPress Task plugin deactivated');
    }
}

// Initialize the plugin
LoginPress_Task_Assessment::get_instance();

