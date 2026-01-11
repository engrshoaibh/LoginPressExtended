import { ToggleControl, TextControl } from '@wordpress/components';

/**
 * Password History Section Component (Task 1)
 */
const PasswordHistorySection = ({ settings, isDisabled, onChange }) => {
    return (
        <div className={`lp-task-subsection lp-task-history ${isDisabled ? 'disabled' : ''}`}>
            <h3>
                <span className="dashicons dashicons-backup"></span>
                Task 1: Password History
            </h3>
            
            <ToggleControl
                label="Disallow Last Password"
                help="Prevent users from reusing their previous passwords"
                checked={settings.disallow_last_password}
                onChange={(value) => onChange('disallow_last_password', value)}
                disabled={isDisabled}
            />

            {settings.disallow_last_password && !isDisabled && (
                <TextControl
                    label="Number of Previous Passwords to Check"
                    help="How many previous passwords to remember and check against (1-10)"
                    type="number"
                    min="1"
                    max="10"
                    value={settings.password_history_count}
                    onChange={(value) => {
                        const num = parseInt(value) || 1;
                        onChange('password_history_count', Math.min(10, Math.max(1, num)));
                    }}
                    disabled={isDisabled}
                />
            )}
        </div>
    );
};

export default PasswordHistorySection;

