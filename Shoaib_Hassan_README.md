# LoginPress Task Assessment - Documentation

**Developer:** Your Name  
**Date:** January 11, 2026  
**Version:** 1.0.0

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture & Design Decisions](#architecture--design-decisions)
3. [Installation & Setup](#installation--setup)
4. [Features Implementation](#features-implementation)
5. [Security Considerations](#security-considerations)
6. [Testing Guide](#testing-guide)
7. [Code Structure](#code-structure)
8. [API Documentation](#api-documentation)
9. [Future Improvements](#future-improvements)

---

## Overview

This WordPress plugin implements a comprehensive password policy management system with two main features:

1. **Password History Check (Task 1):** Prevents users from reusing their previous passwords
2. **Password Expiry Reminder (Task 2):** Sends automated email reminders before passwords expire

The plugin uses React for the admin interface and WordPress REST API for backend communication, following WordPress coding standards and best practices.

---

## Architecture & Design Decisions

### 1. Data Storage: User Meta vs. Custom Table

**Decision: Using WordPress User Meta (`wp_usermeta` table)**

#### Rationale:

**Advantages:**
- **Native WordPress Integration:** User meta is a built-in WordPress feature with robust API functions
- **Automatic User Association:** Data is automatically linked to users and cleaned up on user deletion
- **Query Performance:** For small datasets (3-10 password hashes per user), user meta is highly efficient
- **Simplicity:** No need for custom table management, migrations, or schema updates
- **Caching:** WordPress automatically handles object caching for user meta
- **Maintenance:** No custom SQL queries needed; use WordPress functions

**Why Not a Custom Table?**
- **Overkill for Small Data:** We're only storing 3-10 password hashes per user
- **Additional Complexity:** Requires custom table creation, maintenance, and potential migration issues
- **No Performance Benefit:** For this use case, user meta performs equally well or better
- **More Code to Maintain:** Custom tables require more error handling and edge case management

**Data Structure:**
```php
// Stored in wp_usermeta
_lp_password_history => array(
    0 => '$2y$10$hash1...',  // Most recent
    1 => '$2y$10$hash2...',  // Second most recent
    2 => '$2y$10$hash3...'   // Third most recent (if count = 3)
)
_lp_last_password_update => 1736633400  // Unix timestamp
_lp_last_reminder_sent => '2026-01-11'   // Date string (Y-m-d)
```

### 2. Frontend Framework: React with @wordpress/scripts

**Decision: Using WordPress's official React build tooling**

#### Rationale:
- **Modern UI:** Leverages WordPress's Gutenberg components for consistent UI/UX
- **Zero Configuration:** @wordpress/scripts provides webpack configuration out of the box
- **WordPress Integration:** Seamless integration with WordPress REST API via `@wordpress/api-fetch`
- **Component Library:** Access to professionally designed UI components
- **Future-Proof:** Aligns with WordPress's direction for admin interfaces

### 3. Security Architecture

**Multi-Layer Security Approach:**

1. **REST API Security:**
   - Permission callbacks (`manage_options` capability)
   - WordPress nonce verification
   - Input sanitization (`absint()`, `rest_sanitize_boolean()`)
   - Parameter validation with required fields and types

2. **Password Storage:**
   - Never store plain text passwords
   - Use WordPress's native password hashing (bcrypt via `wp_hash_password()`)
   - Compare using `wp_check_password()` for timing attack prevention

3. **Data Validation:**
   - Server-side validation for all inputs
   - Type checking and range validation
   - Business logic validation (e.g., reminder_days < expiry_days)

---

## Installation & Setup

### Prerequisites
- WordPress 5.8 or higher
- PHP 7.4 or higher
- Node.js 14.x or higher
- npm 6.x or higher

### Step-by-Step Installation

1. **Clone/Download Plugin:**
   ```bash
   cd /path/to/wordpress/wp-content/plugins/
   git clone [repository-url] loginpress-task-assessment
   # OR extract the plugin folder here
   ```

2. **Install Dependencies:**
   ```bash
   cd loginpress-task-assessment
   npm install
   ```

3. **Build the React App:**
   
   For development (with hot reload):
   ```bash
   npm start
   ```
   
   For production (optimized build):
   ```bash
   npm run build
   ```

4. **Activate Plugin:**
   - Go to WordPress Admin Dashboard
   - Navigate to Plugins → Installed Plugins
   - Find "LoginPress Task Assessment"
   - Click "Activate"

5. **Access Settings:**
   - Look for "LoginPress Task" in the admin menu (with shield icon)
   - Configure your password policies

---

## Features Implementation

### Task 1: Password History Check

#### How It Works:

1. **Password Change Detection:**
   - Hooks into `profile_update` action
   - Compares new password hash with previous hash
   - Only triggers when password actually changes

2. **History Storage:**
   - Stores hashed passwords in `_lp_password_history` user meta
   - Maintains array of N most recent passwords (configurable 1-10)
   - Uses array_slice to keep only required number

3. **Validation Process:**
   - Hooks into `wp_authenticate_user` filter
   - Checks new password against all stored hashes
   - Returns `WP_Error` if match found, blocking the change

4. **User Experience:**
   - Clear error message: "You cannot reuse one of your last X passwords"
   - Prevents form submission
   - Requires user to choose a different password

#### Code Flow:
```
User Changes Password
        ↓
wp_authenticate_user filter triggered
        ↓
check_password_history() executes
        ↓
Load password_history_count setting
        ↓
Get _lp_password_history from user meta
        ↓
Loop through stored hashes
        ↓
wp_check_password() for each hash
        ↓
Match found? → Return WP_Error (stops process)
No match? → Continue normally
        ↓
profile_update action triggered
        ↓
store_password_hash() executes
        ↓
Add new hash to history array
        ↓
Trim array to configured size
        ↓
Update user meta
```

#### Edge Cases Handled:
- ✅ New user registration (initializes history)
- ✅ First password change (no previous history)
- ✅ Feature disabled mid-operation (graceful bypass)
- ✅ Invalid/corrupted meta data (defaults to empty array)
- ✅ Duplicate hash prevention (checks before adding)

### Task 2: Password Expiry Reminder

#### How It Works:

1. **Cron Scheduling:**
   - Uses WordPress's native `wp_cron` system
   - Scheduled as daily event: `lp_daily_password_reminder`
   - Auto-schedules on plugin activation
   - Cleans up on deactivation

2. **Reminder Logic:**
   ```
   Days Since Update = Current Date - Last Password Update
   Days Until Expiry = Expiry Days - Days Since Update
   
   Send Reminder IF:
   - Days Until Expiry > 0 AND
   - Days Until Expiry <= Reminder Days
   ```

3. **Email Sending:**
   - Uses WordPress's `wp_mail()` function
   - Personalized subject and message
   - Includes days remaining
   - Links to site

4. **Duplicate Prevention:**
   - Stores `_lp_last_reminder_sent` as date (Y-m-d)
   - Only sends one email per user per day
   - Prevents spam if cron runs multiple times

#### Code Flow:
```
WordPress Cron (Daily)
        ↓
lp_daily_password_reminder hook
        ↓
send_password_reminders() executes
        ↓
Check if feature enabled
        ↓
Get all WordPress users
        ↓
For Each User:
  ├─ Get _lp_last_password_update
  ├─ Calculate days since update
  ├─ Calculate days until expiry
  ├─ Check if in reminder window
  ├─ Check if already sent today
  ├─ Send email via wp_mail()
  └─ Update _lp_last_reminder_sent
```

#### Example Scenario:
- **Settings:** Expiry = 90 days, Reminder = 7 days
- **User:** Changed password 85 days ago
- **Days until expiry:** 90 - 85 = 5 days
- **Action:** Send reminder (5 <= 7)
- **Next day:** 4 days remaining, send again
- **Continues until:** Password is changed or expires

#### Edge Cases Handled:
- ✅ No last_update recorded (skips user)
- ✅ Multiple cron runs same day (duplicate prevention)
- ✅ Email sending failures (catches errors, continues with other users)
- ✅ Feature disabled mid-cycle (stops sending)
- ✅ User changes password (resets timer)

---

## Security Considerations

### 1. Input Sanitization

**PHP (Server-Side):**
```php
// Integer sanitization
$count = absint($request->get_param('password_history_count'));

// Boolean sanitization
'sanitize_callback' => 'rest_sanitize_boolean'

// No SQL injection risk - using WordPress functions only
```

**JavaScript (Client-Side):**
```javascript
// Number validation with min/max
const num = Math.min(10, Math.max(1, parseInt(value) || 1));

// Type checking
if (typeof value !== 'boolean') { /* handle */ }
```

### 2. Authentication & Authorization

```php
// REST API permission callback
public function check_admin_permissions() {
    return current_user_can('manage_options');
}

// WordPress nonce verification (automatic with wp_rest)
wp_localize_script('...', 'lpTaskData', array(
    'nonce' => wp_create_nonce('wp_rest')
));
```

### 3. Password Security

**Never Store Plain Text:**
```php
// ❌ NEVER DO THIS
update_user_meta($user_id, 'password', $_POST['password']);

// ✅ ALWAYS DO THIS
$hash = $user->data->user_pass; // Already hashed by WordPress
update_user_meta($user_id, '_lp_password_history', array($hash));
```

**Secure Comparison:**
```php
// Uses timing-attack-safe comparison
wp_check_password($new_password, $old_hash, $user_id);
```

### 4. XSS Prevention

- All WordPress components automatically escape output
- REST API responses sanitized
- No direct `echo` of user input

### 5. CSRF Protection

- WordPress REST API nonce system
- Automatic verification on all POST requests

---

## Testing Guide

### Manual Testing Checklist

#### Setup Tests:
- [ ] Plugin activates without errors
- [ ] Admin menu item appears with shield icon
- [ ] Settings page loads React interface
- [ ] Default settings are correct

#### Task 1 - Password History:
1. **Enable Feature:**
   - [ ] Enable "Force Password Reset"
   - [ ] Enable "Disallow Last Password"
   - [ ] Set history count to 3
   - [ ] Save settings successfully

2. **Test Password Change:**
   - [ ] Create test user
   - [ ] Change password to "TestPass123!"
   - [ ] Try changing back to "TestPass123!" immediately
   - [ ] Verify error message appears
   - [ ] Change to "DifferentPass456!"
   - [ ] Verify success

3. **Test History Limit:**
   - [ ] Change password 4 times
   - [ ] Verify can reuse password #1 (beyond history count of 3)
   - [ ] Verify cannot reuse passwords #2, #3, #4

4. **Test Disabled State:**
   - [ ] Disable "Disallow Last Password"
   - [ ] Verify can reuse previous passwords
   - [ ] Re-enable and verify blocking resumes

#### Task 2 - Password Expiry:
1. **Enable Feature:**
   - [ ] Enable "Force Password Reset"
   - [ ] Enable "Enable Password Expiry Reminder"
   - [ ] Set expiry to 90 days
   - [ ] Set reminder to 7 days
   - [ ] Save settings successfully

2. **Test Cron Job:**
   ```bash
   # Manual cron trigger (for testing)
   wp cron event run lp_daily_password_reminder
   ```
   - [ ] Verify cron is scheduled
   - [ ] Check email logs for sent reminders

3. **Test Reminder Logic:**
   - [ ] Manually set `_lp_last_password_update` to 83 days ago
   - [ ] Run cron manually
   - [ ] Verify email sent
   - [ ] Check email content and formatting

4. **Test Edge Cases:**
   - [ ] New user (no last_update) - should not send
   - [ ] User with password 10 days old - should not send
   - [ ] User with password 89 days old - should send

#### UI Tests:
- [ ] Master toggle disables/enables sub-sections
- [ ] Visual feedback (opacity) when disabled
- [ ] Number inputs respect min/max values
- [ ] Validation message when reminder_days >= expiry_days
- [ ] Save button disables during save
- [ ] Success notice appears after save
- [ ] Error notice appears on failure

### Automated Testing

```php
// Example PHPUnit test structure (for future implementation)

class Test_Password_History extends WP_UnitTestCase {
    public function test_password_reuse_blocked() {
        // Create user
        $user_id = $this->factory->user->create();
        
        // Enable feature
        update_option('lp_task_settings', array(
            'force_password_reset' => true,
            'disallow_last_password' => true,
            'password_history_count' => 3
        ));
        
        // Change password
        wp_set_password('password1', $user_id);
        
        // Try to reuse
        $_POST['pass1'] = 'password1';
        $result = check_password_history($user_id, 'password1');
        
        $this->assertWPError($result);
    }
}
```

---

## Code Structure

### File Organization

```
loginpress-task-assessment/
│
├── loginpress-task.php          # Main plugin file (458 lines)
│   ├── Class: LoginPress_Task_Assessment
│   ├── Admin menu registration
│   ├── REST API endpoints
│   ├── Password history logic
│   ├── Cron job management
│   └── Email functionality
│
├── src/                         # React source files
│   ├── index.js                # React entry point
│   └── components/
│       ├── App.js              # Main component (state, API calls)
│       └── App.css             # Styling
│
├── build/                      # Compiled output (generated)
│   ├── index.js               # Bundled JavaScript
│   ├── index.asset.php        # Dependency manifest
│   └── index.css              # Compiled styles
│
├── node_modules/              # npm dependencies
├── package.json               # Node dependencies & scripts
├── package-lock.json          # Dependency lock file
└── YOURNAME_README.md         # This documentation
```

### Key PHP Functions

| Function | Purpose | Hook/Filter |
|----------|---------|-------------|
| `register_admin_menu()` | Add admin page | `admin_menu` |
| `enqueue_admin_scripts()` | Load React app | `admin_enqueue_scripts` |
| `register_rest_routes()` | Create API endpoints | `rest_api_init` |
| `check_password_history()` | Validate new password | `wp_authenticate_user` |
| `store_password_hash()` | Save password hash | `profile_update` |
| `schedule_reminder_cron()` | Setup cron job | `init` |
| `send_password_reminders()` | Process reminders | `lp_daily_password_reminder` |

### Database Schema

**Options Table (`wp_options`):**
```sql
option_name: lp_task_settings
option_value: {
    "force_password_reset": false,
    "disallow_last_password": false,
    "password_history_count": 3,
    "enable_reminder": false,
    "expiry_days": 90,
    "reminder_days": 7
}
```

**User Meta Table (`wp_usermeta`):**
```sql
-- Password history (per user)
meta_key: _lp_password_history
meta_value: ["$2y$10$hash1", "$2y$10$hash2", "$2y$10$hash3"]

-- Last password update timestamp (per user)
meta_key: _lp_last_password_update
meta_value: 1736633400

-- Last reminder sent date (per user)
meta_key: _lp_last_reminder_sent
meta_value: 2026-01-11
```

---

## API Documentation

### REST API Endpoints

#### 1. Get Settings

**Endpoint:** `GET /wp-json/loginpress-task/v1/settings`

**Permission:** Requires `manage_options` capability

**Response:**
```json
{
    "force_password_reset": false,
    "disallow_last_password": false,
    "password_history_count": 3,
    "enable_reminder": false,
    "expiry_days": 90,
    "reminder_days": 7
}
```

**Example:**
```javascript
const settings = await apiFetch({
    path: '/loginpress-task/v1/settings',
    method: 'GET'
});
```

#### 2. Save Settings

**Endpoint:** `POST /wp-json/loginpress-task/v1/settings`

**Permission:** Requires `manage_options` capability

**Request Body:**
```json
{
    "force_password_reset": true,
    "disallow_last_password": true,
    "password_history_count": 5,
    "enable_reminder": true,
    "expiry_days": 90,
    "reminder_days": 7
}
```

**Success Response:**
```json
{
    "success": true,
    "message": "Settings saved successfully.",
    "settings": {
        "force_password_reset": true,
        "disallow_last_password": true,
        "password_history_count": 5,
        "enable_reminder": true,
        "expiry_days": 90,
        "reminder_days": 7
    }
}
```

**Error Response:**
```json
{
    "code": "invalid_reminder_days",
    "message": "Reminder days must be less than expiry days.",
    "data": {
        "status": 400
    }
}
```

**Validation Rules:**
- `password_history_count`: Integer, 1-10
- `expiry_days`: Integer, 1-365
- `reminder_days`: Integer, 1 to (expiry_days - 1)
- Booleans sanitized automatically

---

## Future Improvements

### Short-Term Enhancements:

1. **Password Strength Meter:**
   - Integrate with WordPress's password strength indicator
   - Enforce minimum strength requirements

2. **Admin Notifications:**
   - Dashboard widget showing users with expiring passwords
   - Email admins when users ignore multiple reminders

3. **Localization:**
   - Add translation support (.pot file)
   - Translate all user-facing strings

4. **Logging:**
   - Log password change attempts (success/failure)
   - Track reminder emails sent
   - Admin view of logs

### Medium-Term Enhancements:

5. **Force Password Change:**
   - Lock users out until password is changed after expiry
   - Grace period option

6. **Custom Password Rules:**
   - Minimum length, special characters, numbers
   - Dictionary word blocking
   - Username similarity check

7. **User Profile Integration:**
   - Show password age on user profile
   - Display last change date
   - Manual "send reminder" button for admins

8. **Multisite Support:**
   - Network-wide settings
   - Per-site overrides
   - Centralized user management

### Long-Term Enhancements:

9. **Two-Factor Authentication:**
   - Integrate with 2FA plugins
   - Require 2FA for password changes

10. **Compliance Reports:**
    - Export user password age data
    - Compliance dashboard
    - HIPAA/GDPR reporting

11. **Password Breach Detection:**
    - Check against Have I Been Pwned API
    - Warn users of compromised passwords

12. **Role-Based Policies:**
    - Different rules for different user roles
    - Admins: 60 days, Subscribers: 90 days

---

## Troubleshooting

### Common Issues:

**1. React App Not Loading:**
```bash
# Rebuild the app
npm run build

# Check for build errors
npm start
```

**2. Cron Not Running:**
```bash
# Check if cron is scheduled
wp cron event list

# Manually run cron
wp cron event run lp_daily_password_reminder
```

**3. Emails Not Sending:**
- Check WordPress email configuration
- Install WP Mail SMTP plugin
- Check spam folder
- Verify `wp_mail()` is working

**4. Password Check Not Working:**
- Verify "Force Password Reset" is enabled
- Verify "Disallow Last Password" is enabled
- Check that user has password history stored
- Look for PHP errors in debug.log

---

## Conclusion

This plugin demonstrates a production-ready approach to WordPress plugin development with:

- ✅ Modern React UI using WordPress components
- ✅ RESTful API architecture
- ✅ Security best practices
- ✅ Proper WordPress coding standards
- ✅ Scalable data architecture
- ✅ Comprehensive error handling
- ✅ User-friendly interface
- ✅ Extensible codebase

The choice of user meta over custom tables provides the right balance of simplicity, performance, and maintainability for this use case. The plugin is ready for real-world deployment and can be easily extended with additional features.

---

**Questions or Issues?**  
Contact: your.email@example.com  
GitHub: [your-github-username]  
Support: [support-link]

