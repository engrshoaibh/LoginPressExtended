import { ToggleControl, TextControl, Notice } from '@wordpress/components';

/**
 * Password Reminder Section Component (Task 2)
 */
const PasswordReminderSection = ({ settings, isDisabled, onChange }) => {
    const hasValidationError = settings.reminder_days >= settings.expiry_days;

    return (
        <div className={`lp-task-subsection lp-task-reminder ${isDisabled ? 'disabled' : ''}`}>
            <h3>
                <span className="dashicons dashicons-email-alt"></span>
                Task 2: Password Expiry Reminder
            </h3>
            
            <ToggleControl
                label="Enable Password Expiry Reminder"
                help="Send email reminders to users before their password expires"
                checked={settings.enable_reminder}
                onChange={(value) => onChange('enable_reminder', value)}
                disabled={isDisabled}
            />

            {settings.enable_reminder && !isDisabled && (
                <div className="lp-task-reminder-fields">
                    <TextControl
                        label="Password Expiry (Days)"
                        help="How many days before a password expires"
                        type="number"
                        min="1"
                        max="365"
                        value={settings.expiry_days}
                        onChange={(value) => {
                            const num = parseInt(value) || 30;
                            onChange('expiry_days', Math.min(365, Math.max(1, num)));
                        }}
                        disabled={isDisabled}
                    />

                    <TextControl
                        label="Reminder Days Before Expiry"
                        help={`Send reminder this many days before expiry (must be less than ${settings.expiry_days})`}
                        type="number"
                        min="1"
                        max={settings.expiry_days - 1}
                        value={settings.reminder_days}
                        onChange={(value) => {
                            const num = parseInt(value) || 7;
                            onChange('reminder_days', Math.min(settings.expiry_days - 1, Math.max(1, num)));
                        }}
                        disabled={isDisabled}
                    />

                    {hasValidationError && (
                        <Notice status="warning" isDismissible={false}>
                            Reminder days must be less than expiry days!
                        </Notice>
                    )}
                </div>
            )}
        </div>
    );
};

export default PasswordReminderSection;

