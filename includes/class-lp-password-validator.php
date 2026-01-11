<?php
/**
 * Password Validation Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class LP_Password_Validator {
    
    /**
     * Validate password change on profile update
     */
    public function validate_on_profile_update($errors, $update, $user) {
        error_log('=== PROFILE PASSWORD VALIDATION START ===');
        
        if (!$update || empty($_POST['pass1']) || empty($_POST['pass2'])) {
            error_log('Not a password update, skipping');
            return;
        }
        
        $user_id = isset($user->ID) ? $user->ID : 0;
        $new_password = $_POST['pass1'];
        
        $this->check_password_history($user_id, $new_password, $errors);
        
        error_log('=== PROFILE PASSWORD VALIDATION END ===');
    }
    
    /**
     * Validate password on reset
     */
    public function validate_on_reset($errors, $user) {
        error_log('=== PASSWORD RESET VALIDATION START ===');
        
        if (empty($_POST['pass1'])) {
            return;
        }
        
        $new_password = $_POST['pass1'];
        $this->check_password_history($user->ID, $new_password, $errors);
        
        error_log('=== PASSWORD RESET VALIDATION END ===');
    }
    
    /**
     * Check if password exists in history
     */
    private function check_password_history($user_id, $new_password, $errors) {
        $settings = get_option(LP_Constants::OPTION_NAME);
        
        // Check if feature is enabled
        if (empty($settings['force_password_reset']) || empty($settings['disallow_last_password'])) {
            error_log('Feature DISABLED');
            return;
        }
        
        error_log('Checking password history for user ' . $user_id);
        
        $history_count = absint($settings['password_history_count']);
        $password_history = get_user_meta($user_id, LP_Constants::META_PASSWORD_HISTORY, true);
        
        if (!is_array($password_history) || empty($password_history)) {
            error_log('No password history found');
            return;
        }
        
        error_log('Checking against ' . count($password_history) . ' stored passwords');
        
        // Check each stored hash
        foreach ($password_history as $index => $old_hash) {
            if (wp_check_password($new_password, $old_hash, $user_id)) {
                error_log('❌ Password reuse detected at index ' . $index);
                
                $errors->add(
                    'password_previously_used',
                    sprintf(
                        __('You cannot reuse one of your last %d passwords. Please choose a different password.', LP_Constants::TEXT_DOMAIN),
                        $history_count
                    )
                );
                return;
            }
        }
        
        error_log('✓ Password is unique - validation passed');
    }
}

