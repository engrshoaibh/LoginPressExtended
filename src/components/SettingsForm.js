import { Card, CardBody, CardHeader, ToggleControl } from '@wordpress/components';
import PasswordHistorySection from './PasswordHistorySection';
import PasswordReminderSection from './PasswordReminderSection';

/**
 * Settings Form Component
 */
const SettingsForm = ({ settings, onSettingChange }) => {
    const isMasterDisabled = !settings.force_password_reset;

    return (
        <Card>
            <CardHeader>
                <h2>Password Policy Settings</h2>
            </CardHeader>
            <CardBody>
                {/* Master Toggle */}
                <div className="lp-task-setting">
                    <ToggleControl
                        label="Force Password Reset"
                        help="Enable password policies for users"
                        checked={settings.force_password_reset}
                        onChange={(value) => onSettingChange('force_password_reset', value)}
                    />
                </div>

                {/* Task 1: Password History */}
                <PasswordHistorySection
                    settings={settings}
                    isDisabled={isMasterDisabled}
                    onChange={onSettingChange}
                />

                {/* Task 2: Password Expiry Reminder */}
                <PasswordReminderSection
                    settings={settings}
                    isDisabled={isMasterDisabled}
                    onChange={onSettingChange}
                />
            </CardBody>
        </Card>
    );
};

export default SettingsForm;

