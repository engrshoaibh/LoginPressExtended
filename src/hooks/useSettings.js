import { useState, useEffect } from '@wordpress/element';
import settingsAPI from '../services/api';

/**
 * Custom hook for settings management
 */
export const useSettings = () => {
    const [settings, setSettings] = useState({
        force_password_reset: false,
        disallow_last_password: false,
        password_history_count: 3,
        enable_reminder: false,
        expiry_days: 90,
        reminder_days: 7
    });
    
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);

    // Load settings on mount
    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        setLoading(true);
        const result = await settingsAPI.getSettings();
        
        if (result.success) {
            setSettings(result.data);
            setError(null);
        } else {
            setError(result.error);
        }
        
        setLoading(false);
    };

    const saveSettings = async (newSettings) => {
        setSaving(true);
        const result = await settingsAPI.saveSettings(newSettings);
        setSaving(false);
        
        if (result.success) {
            setSettings(result.data.settings);
            setError(null);
            return { success: true, message: result.data.message };
        } else {
            setError(result.error);
            return { success: false, error: result.error };
        }
    };

    const updateSetting = (key, value) => {
        setSettings(prev => ({ ...prev, [key]: value }));
    };

    return {
        settings,
        loading,
        saving,
        error,
        updateSetting,
        saveSettings,
        reloadSettings: loadSettings
    };
};

