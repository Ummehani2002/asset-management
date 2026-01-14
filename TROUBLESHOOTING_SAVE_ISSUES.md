# Troubleshooting: Data Not Saving / No Success Messages

## Problem
When submitting forms in master pages (Employee, Location, Category, etc.):
- Data is not being saved to database
- Success messages are not showing
- No error messages appear

## Root Causes & Solutions

### 1. **Migrations Not Run** ⚠️ MOST COMMON

**Symptoms:**
- Tables don't exist in database
- All forms show 500 errors or warnings

**Solution:**
1. Go to Laravel Cloud → Your Site → `main` environment
2. Click **Commands** tab (or Console)
3. Run: `php artisan migrate --force`
4. Wait for completion
5. Verify: `php artisan migrate:status`

**Critical Tables Needed:**
- `employees`
- `locations`
- `asset_categories`
- `brands`
- `projects`
- `sessions` (for success messages!)

---

### 2. **Sessions Table Missing** ⚠️ CRITICAL FOR SUCCESS MESSAGES

**Symptoms:**
- Data saves but success messages don't appear
- Forms redirect but no feedback

**Solution:**
The `sessions` table is created in the `create_users_table` migration. If migrations haven't run, this table won't exist.

**Check if sessions table exists:**
```bash
php artisan tinker
>>> Schema::hasTable('sessions')
```

**If false, run:**
```bash
php artisan migrate --force
```

**Alternative: Use file-based sessions (temporary fix)**
In Laravel Cloud environment variables:
```
SESSION_DRIVER=file
```

Then clear config cache:
```bash
php artisan config:clear
```

---

### 3. **Database Connection Issues**

**Symptoms:**
- Silent failures
- No error messages
- Data not persisting

**Check:**
1. Verify database credentials in Laravel Cloud environment variables
2. Test connection:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```
3. Check logs: `storage/logs/laravel.log`

**Common Issues:**
- Wrong `DB_HOST` (should be from Laravel Cloud, not `127.0.0.1`)
- Database not created yet
- Wrong database name/username/password

---

### 4. **Validation Errors Not Showing**

**Symptoms:**
- Form submits but nothing happens
- No error messages displayed

**Check:**
1. Look at browser console for JavaScript errors
2. Check if validation errors are being displayed in views
3. Check Laravel logs: `storage/logs/laravel.log`

**In views, ensure you have:**
```blade
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

---

### 5. **Check Application Logs**

**In Laravel Cloud:**
1. Go to your site → `main` environment
2. Click **Logs** tab
3. Look for:
   - `Creating employee with data:` - confirms form submission
   - `Employee created successfully. ID: X` - confirms save
   - `Employee was not saved to database!` - indicates save failure
   - Database errors

**Or via Console:**
```bash
tail -f storage/logs/laravel.log
```

---

## Quick Diagnostic Steps

### Step 1: Verify Migrations
```bash
php artisan migrate:status
```
All migrations should show "Ran".

### Step 2: Check Sessions Table
```bash
php artisan tinker
>>> Schema::hasTable('sessions')
```
Should return `true`.

### Step 3: Test Database Connection
```bash
php artisan tinker
>>> DB::table('employees')->count()
```
Should return a number (even if 0).

### Step 4: Check Environment Variables
In Laravel Cloud → Settings → Environment Variables, verify:
- `DB_CONNECTION=mysql`
- `DB_HOST` (from Laravel Cloud, not `127.0.0.1`)
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `SESSION_DRIVER=database` (or `file` if sessions table missing)

### Step 5: Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## Most Likely Solution

**99% of the time, the issue is:**

1. **Migrations not run** → Run `php artisan migrate --force`
2. **Sessions table missing** → Part of migrations, will be created when migrations run
3. **Database connection wrong** → Check environment variables in Laravel Cloud

---

## After Fixing

1. Test creating an employee
2. Check if it appears in the list
3. Check if success message appears
4. Check logs to confirm save operation

---

## Still Not Working?

1. Check Laravel Cloud logs
2. Check browser console for JavaScript errors
3. Verify CSRF token is being sent (check form has `@csrf`)
4. Check if middleware is blocking requests
5. Verify file permissions on `storage/logs` directory

---

## Contact Support

If still not working after all steps:
1. Share Laravel Cloud logs
2. Share output of `php artisan migrate:status`
3. Share database connection test results
4. Share any error messages from browser console
