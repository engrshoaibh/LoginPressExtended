<?php
/**
 * Admin Page Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class LP_Admin {
    
    /**
     * Register admin menu
     */
    public function register_menu() {
        add_menu_page(
            __('LoginPress Task', LP_Constants::TEXT_DOMAIN),
            __('LoginPress Task', LP_Constants::TEXT_DOMAIN),
            'manage_options',
            LP_Constants::MENU_SLUG,
            array($this, 'render_page'),
            'dashicons-shield-alt',
            30
        );
    }
    
    /**
     * Render admin page
     */
    public function render_page() {
        echo '<div id="loginpress-task-root"></div>';
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ('toplevel_page_' . LP_Constants::MENU_SLUG !== $hook) {
            return;
        }
        
        $asset_file = include(plugin_dir_path(dirname(__FILE__)) . 'build/index.asset.php');
        
        wp_enqueue_script(
            'loginpress-task-admin',
            plugins_url('build/index.js', dirname(__FILE__)),
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );
        
        wp_enqueue_style(
            'loginpress-task-admin',
            plugins_url('build/index.css', dirname(__FILE__)),
            array('wp-components', 'dashicons'),
            $asset_file['version']
        );
        
        wp_localize_script('loginpress-task-admin', 'lpTaskData', array(
            'restUrl' => rest_url('loginpress-task/v1'),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }
}

