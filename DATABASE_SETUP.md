# ColdKey GMI - Database & Activity Logging Setup

## ✅ Completed Tasks

### 1. Database Creation

- **Database Name**: `coldkeygmi`
- **Connection**: MySQL (127.0.0.1:3306)
- **Character Set**: utf8mb4 with Unicode collation

### 2. Users Table

**Table Name**: `users`

**Columns**:

- `id` - Primary key (auto-increment)
- `first_name` - User's first name
- `last_name` - User's last name
- `email` - Unique email address
- `password` - Hashed password
- `status` - enum('active', 'deactivated') - **Default: 'deactivated'** (all new signups start deactivated)
- `department` - Optional department
- `job_position` - Optional job position
- `work_position` - Optional work position
- `created_date` - Timestamp when user created (auto-generated)
- `updated_date` - Timestamp when user last updated (auto-updated)
- `user_created` - User/system that created the record (default: 'system')
- `user_updated` - User/system that last updated the record (default: 'system')
- `remember_token` - Laravel remember me token

**Sample Data** (6 users):

1. Admin User (active) - admin@coldkeygmi.com
2. John Doe (deactivated) - john@example.com
3. Jane Smith (deactivated) - jane@example.com
4. Bob Johnson (deactivated) - bob@example.com
5. Alice Williams (deactivated) - alice@example.com
6. Charlie Brown (deactivated) - charlie@example.com

### 3. Activity Logs Table

**Table Name**: `activity_logs`

**Columns**:

- `id` - Primary key (auto-increment)
- `table_name` - Name of the affected table (e.g., 'users')
- `record_id` - ID of the affected record
- `action` - enum('insert', 'update', 'delete')
- `old_values` - JSON of previous values (for updates/deletes)
- `new_values` - JSON of new values (for inserts/updates)
- `user_id` - ID of user who made the change (default: 'system')
- `user_email` - Email of user who made the change
- `ip_address` - IP address from which the change was made
- `created_date` - Timestamp of the action
- `description` - Human-readable description of the action

**Indexes**: On `table_name`, `record_id`, `action`, and `created_date` for fast querying

### 4. Models Created/Updated

#### User Model (`app/Models/User.php`)

```php
- Uses HasFactory and Notifiable traits
- Mass fillable: first_name, last_name, email, password, status, department, job_position, work_position, user_created, user_updated
- Custom attributes: getFullNameAttribute()
- Status methods: isActive(), activate($user), deactivate($user)
- Timestamps disabled (using custom created_date/updated_date)
```

#### ActivityLog Model (`app/Models/ActivityLog.php`)

```php
- Stores all database activity logs
- Methods: allLogs(), getRecordHistory(), getByAction()
- JSON casting for old_values and new_values
```

### 5. Observer Pattern

**UserObserver** (`app/Observers/UserObserver.php`)

- Automatically triggered on User model changes
- Events tracked:
    - `created()` - Logs INSERT actions
    - `updated()` - Logs UPDATE actions with field changes
    - `deleted()` - Logs DELETE actions
    - `restored()` - Logs RESTORE/REACTIVATION actions
- Records: table_name, record_id, action, old_values, new_values, user_id, user_email, ip_address, created_date, description

### 6. Registration Flow

**Route**: `POST /signup` → UserController@store

**Features**:

- Validates: firstName, lastName, email, password (confirmed, 8+ chars, mixed case, numbers, symbols), acceptTerms
- All new users created with status = 'deactivated'
- Activity log automatically created for each signup
- Stores: user_created = requester's IP address (for guest signups)

### 7. Registration Validation Rules

```php
- firstName: required|string|max:255
- lastName: required|string|max:255
- email: required|email|unique:users,email
- password: required|confirmed|Password::min(8)->mixedCase()->numbers()->symbols()
- acceptTerms: required|accepted
- department: nullable|string (optional)
- jobPosition: nullable|string (optional)
- workPosition: nullable|string (optional)
```

## Database Diagram

```
┌─────────────────────────────────┐
│           users                 │
├─────────────────────────────────┤
│ id (PK)                         │
│ first_name                      │
│ last_name                       │
│ email (UNIQUE)                  │
│ password                        │
│ status (active|deactivated)     │
│ department                      │
│ job_position                    │
│ work_position                   │
│ created_date                    │
│ updated_date                    │
│ user_created                    │
│ user_updated                    │
│ remember_token                  │
└─────────────────────────────────┘
           ▲
           │ (1:many)
           │
┌─────────────────────────────────┐
│      activity_logs              │
├─────────────────────────────────┤
│ id (PK)                         │
│ table_name                      │
│ record_id (FK)                  │
│ action (insert|update|delete)   │
│ old_values (JSON)               │
│ new_values (JSON)               │
│ user_id                         │
│ user_email                      │
│ ip_address                      │
│ created_date                    │
│ description                     │
└─────────────────────────────────┘
```

## Activity Logging Example

When a user submits the signup form:

```json
{
    "table_name": "users",
    "record_id": 7,
    "action": "insert",
    "old_values": null,
    "new_values": {
        "first_name": "Test",
        "last_name": "User",
        "email": "test@test.com",
        "status": "deactivated",
        "department": "Engineering",
        "job_position": "Developer",
        "work_position": "Senior",
        "user_created": "192.168.1.100",
        "user_updated": "192.168.1.100"
    },
    "user_id": "system",
    "user_email": "system",
    "ip_address": "192.168.1.100",
    "created_date": "2026-02-19 13:30:45",
    "description": "User test@test.com created by system"
}
```

## Key Implementation Details

### Observer Registration

In `app/Providers/AppServiceProvider.php`:

```php
public function boot(): void
{
    User::observe(UserObserver::class);
}
```

### Automatic Status on Signup

All new users are created with `status = 'deactivated'` and require admin activation.

### Audit Trail Fields

- `user_created` - Tracks who created the record
- `user_updated` - Tracks who last modified the record
- Created/updated dates are automatically managed by MySQL CURRENT_TIMESTAMP

### Activity Log Querying

```php
// Get all logs
ActivityLog::allLogs();

// Get logs for a specific table
ActivityLog::allLogs('users');

// Get history for a specific user
ActivityLog::getRecordHistory('users', 7);

// Get all delete operations
ActivityLog::getByAction('delete');
```

## Next Steps

1. **User Activation**: Create admin panel to activate/deactivate users
2. **Email Notifications**: Send confirmation emails when users signup
3. **Admin Dashboard**: Display activity logs and user management
4. **Search & Filter**: Filter activity logs by date range, user, action type
5. **Report Generation**: Generate audit reports from activity logs

---

**Created**: February 19, 2026
**Environment**: Development (XAMPP MySQL)
