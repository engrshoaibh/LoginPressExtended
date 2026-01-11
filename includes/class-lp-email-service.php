<?php
/**
 * Email Reminder Service
 */

if (!defined('ABSPATH')) {
    exit;
}

class LP_Email_Service {
    
    /**
     * Schedule cron job
     */
    public function schedule_cron() {
        if (!wp_next_scheduled(LP_Constants::CRON_HOOK)) {
            wp_schedule_event(time(), 'daily', LP_Constants::CRON_HOOK);
            error_log('Scheduled daily password reminder cron');
        }
    }
    
    /**
     * Send password expiry reminders
     */
    public function send_reminders() {
        error_log('=== SENDING PASSWORD REMINDERS START ===');
        
        $settings = get_option(LP_Constants::OPTION_NAME);
        
        // Check if feature is enabled
        if (empty($settings['force_password_reset']) || empty($settings['enable_reminder'])) {
            error_log('Reminder feature disabled');
            return;
        }
        
        $expiry_days = absint($settings['expiry_days']);
        $reminder_days = absint($settings['reminder_days']);
        
        error_log("Checking users with expiry: {$expiry_days} days, reminder: {$reminder_days} days");
        
        $users = get_users(array('fields' => 'all'));
        $sent_count = 0;
        
        foreach ($users as $user) {
            if ($this->should_send_reminder($user->ID, $expiry_days, $reminder_days)) {
                $days_left = $this->get_days_until_expiry($user->ID, $expiry_days);
                
                if ($this->send_reminder_email($user, $days_left)) {
                    $sent_count++;
                    update_user_meta($user->ID, LP_Constants::META_LAST_REMINDER, date('Y-m-d'));
                }
            }
        }
        
        error_log("Sent {$sent_count} reminder emails");
        error_log('=== SENDING PASSWORD REMINDERS END ===');
    }
    
    /**
     * Check if reminder should be sent
     */
    private function should_send_reminder($user_id, $expiry_days, $reminder_days) {
        $last_update = get_user_meta($user_id, LP_Constants::META_LAST_UPDATE, true);
        
        if (empty($last_update)) {
            return false;
        }
        
        $days_until_expiry = $this->get_days_until_expiry($user_id, $expiry_days);
        
        // Send if within reminder window
        if ($days_until_expiry <= 0 || $days_until_expiry > $reminder_days) {
            return false;
        }
        
        // Check if already sent today
        $last_reminder = get_user_meta($user_id, LP_Constants::META_LAST_REMINDER, true);
        $today = date('Y-m-d');
        
        if ($last_reminder === $today) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Calculate days until password expires
     */
    private function get_days_until_expiry($user_id, $expiry_days) {
        $last_update = get_user_meta($user_id, LP_Constants::META_LAST_UPDATE, true);
        $days_since_update = floor((current_time('timestamp') - $last_update) / DAY_IN_SECONDS);
        return $expiry_days - $days_since_update;
    }
    
    /**
     * Send reminder email to user
     */
    private function send_reminder_email($user, $days_until_expiry) {
        $subject = sprintf(
            __('Password Expiry Reminder - %d days remaining', LP_Constants::TEXT_DOMAIN),
            $days_until_expiry
        );
        
        $message = sprintf(
            __('Hi %s,

This is a reminder that your password will expire in %d day(s).

Please log in to %s and update your password to maintain access to your account.

If you have any questions, please contact the site administrator.

Thank you.', LP_Constants::TEXT_DOMAIN),
            $user->display_name,
            $days_until_expiry,
            get_bloginfo('name')
        );
        
        $sent = wp_mail($user->user_email, $subject, $message);
        
        if ($sent) {
            error_log("✓ Email sent to {$user->user_email} ({$days_until_expiry} days left)");
        } else {
            error_log("✗ Failed to send email to {$user->user_email}");
        }
        
        return $sent;
    }
}

