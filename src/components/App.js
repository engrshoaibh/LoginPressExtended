import { useState, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import settingsAPI from '../services/api';
import LoadingState from './LoadingState';
import NoticeMessage from './NoticeMessage';
import SettingsForm from './SettingsForm';
import './App.css';

/**
 * Main App Component - Refactored
 */
const App = () => {
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
    const [notice, setNotice] = useState(null);

    // Load settings on mount
    useEffect(() => {
        loadSettings();
    }, []);

    /**
     * Load settings from API
     */
    const loadSettings = async () => {
        const result = await settingsAPI.getSettings();
        
        if (result.success) {
            setSettings(result.data);
        } else {
            setNotice({
                type: 'error',
                message: 'Failed to load settings: ' + result.error
            });
        }
        
        setLoading(false);
    };

    /**
     * Save settings to API
     */
    const handleSave = async () => {
        setSaving(true);
        setNotice(null);

        const result = await settingsAPI.saveSettings(settings);

        if (result.success) {
            setNotice({
                type: 'success',
                message: result.data.message || 'Settings saved successfully!'
            });
        } else {
            setNotice({
                type: 'error',
                message: 'Failed to save settings: ' + result.error
            });
        }

        setSaving(false);
    };

    /**
     * Update individual setting
     */
    const updateSetting = (key, value) => {
        setSettings(prev => ({
            ...prev,
            [key]: value
        }));
    };

    /**
     * Check if save button should be disabled
     */
    const isSaveDisabled = () => {
        return saving || (settings.reminder_days >= settings.expiry_days);
    };

    if (loading) {
        return <LoadingState />;
    }

    return (
        <div className="lp-task-container">
            <div className="lp-task-header">
                <h1>LoginPress Task Assessment</h1>
                <p>Configure password policies and expiry reminders</p>
            </div>

            <NoticeMessage 
                notice={notice} 
                onDismiss={() => setNotice(null)} 
            />

            <SettingsForm 
                settings={settings} 
                onSettingChange={updateSetting} 
            />

            <div className="lp-task-actions">
                <Button 
                    variant="primary" 
                    onClick={handleSave}
                    isBusy={saving}
                    disabled={isSaveDisabled()}
                >
                    {saving ? 'Saving...' : 'Save Settings'}
                </Button>
            </div>
        </div>
    );
};

export default App;

