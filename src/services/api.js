import apiFetch from '@wordpress/api-fetch';

/**
 * API Service for settings management
 */
class SettingsAPI {
    constructor() {
        this.baseUrl = '/loginpress-task/v1';
    }

    /**
     * Get current settings
     */
    async getSettings() {
        try {
            const response = await apiFetch({
                path: `${this.baseUrl}/settings`,
                method: 'GET'
            });
            return { success: true, data: response };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    /**
     * Save settings
     */
    async saveSettings(settings) {
        try {
            const response = await apiFetch({
                path: `${this.baseUrl}/settings`,
                method: 'POST',
                data: settings
            });
            return { success: true, data: response };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }
}

export default new SettingsAPI();

