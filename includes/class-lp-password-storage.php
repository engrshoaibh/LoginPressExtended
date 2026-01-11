<?php
/**
 * Password Storage Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class LP_Password_Storage {
    
    /**
     * Store password hash after profile update
     */
    public function store_on_update($user_id, $old_user_data = null) {
        error_log('=== STORE PASSWORD HASH START ===');
        error_log('User ID: ' . $user_id);
        
        $settings = get_option(LP_Constants::OPTION_NAME);
        
        // Only store if feature is enabled
        if (empty($settings['force_password_reset']) || empty($settings['disallow_last_password'])) {
            error_log('Feature disabled - not storing');
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            error_log('User not found');
            return;
        }
        
        $current_hash = $user->data->user_pass;
        $this->add_to_history($user_id, $current_hash, $settings);
        
        error_log('=== STORE PASSWORD HASH END ===');
    }
    
    /**
     * Initialize password data for new users
     */
    public function initialize_for_new_user($user_id) {
        error_log('=== INITIALIZE NEW USER PASSWORD DATA ===');
        
        $settings = get_option(LP_Constants::OPTION_NAME);
        
        if (empty($settings['force_password_reset'])) {
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        $current_hash = $user->data->user_pass;
        $this->add_to_history($user_id, $current_hash, $settings);
        
        error_log('Initialized password data for user ' . $user_id);
    }
    
    /**
     * Add password hash to history
     */
    private function add_to_history($user_id, $current_hash, $settings) {
        $password_history = get_user_meta($user_id, LP_Constants::META_PASSWORD_HISTORY, true);
        
        if (!is_array($password_history)) {
            $password_history = array();
        }
        
        // Avoid duplicates
        if (!empty($password_history) && $password_history[0] === $current_hash) {
            error_log('Hash already stored - skipping duplicate');
            return;
        }
        
        // Add to beginning
        array_unshift($password_history, $current_hash);
        error_log('Added hash to history. Count: ' . count($password_history));
        
        // Trim to configured size
        $history_count = absint($settings['password_history_count']);
        $password_history = array_slice($password_history, 0, $history_count);
        
        // Save to database
        update_user_meta($user_id, LP_Constants::META_PASSWORD_HISTORY, $password_history);
        update_user_meta($user_id, LP_Constants::META_LAST_UPDATE, current_time('timestamp'));
        
        error_log('Saved ' . count($password_history) . ' passwords to history');
    }
}

