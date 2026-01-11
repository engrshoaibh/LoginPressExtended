<?php
/**
 * REST API Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class LP_REST_API {
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        register_rest_route('loginpress-task/v1', '/settings', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_settings'),
                'permission_callback' => array($this, 'check_permissions')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'save_settings'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => $this->get_settings_schema()
            )
        ));
    }
    
    /**
     * Check user permissions
     */
    public function check_permissions() {
        return current_user_can('manage_options');
    }
    
    /**
     * Get settings endpoint
     */
    public function get_settings($request) {
        $settings = get_option(LP_Constants::OPTION_NAME, LP_Constants::get_default_settings());
        return rest_ensure_response($settings);
    }
    
    /**
     * Save settings endpoint
     */
    public function save_settings($request) {
        $settings = array(
            'force_password_reset' => $request->get_param('force_password_reset'),
            'disallow_last_password' => $request->get_param('disallow_last_password'),
            'password_history_count' => absint($request->get_param('password_history_count')),
            'enable_reminder' => $request->get_param('enable_reminder'),
            'expiry_days' => absint($request->get_param('expiry_days')),
            'reminder_days' => absint($request->get_param('reminder_days'))
        );
        
        // Validate
        if ($settings['reminder_days'] >= $settings['expiry_days']) {
            return new WP_Error(
                'invalid_reminder_days',
                __('Reminder days must be less than expiry days.', LP_Constants::TEXT_DOMAIN),
                array('status' => 400)
            );
        }
        
        update_option(LP_Constants::OPTION_NAME, $settings);
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Settings saved successfully.', LP_Constants::TEXT_DOMAIN),
            'settings' => $settings
        ));
    }
    
    /**
     * Get settings schema for validation
     */
    private function get_settings_schema() {
        return array(
            'force_password_reset' => array(
                'required' => true,
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean'
            ),
            'disallow_last_password' => array(
                'required' => true,
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean'
            ),
            'password_history_count' => array(
                'required' => true,
                'type' => 'integer',
                'sanitize_callback' => 'absint'
            ),
            'enable_reminder' => array(
                'required' => true,
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean'
            ),
            'expiry_days' => array(
                'required' => true,
                'type' => 'integer',
                'sanitize_callback' => 'absint'
            ),
            'reminder_days' => array(
                'required' => true,
                'type' => 'integer',
                'sanitize_callback' => 'absint'
            )
        );
    }
}

