# 🔧 Fix Database Error in Production

## Current Error
**"Database error occurred. Please ensure migrations are run: php artisan migrate --force"**

This error can appear on:
- `/internet-services/create` (when creating or loading Internet Service form)
- `/projects/create` and other create/store pages

---

## ✅ Quick Fix Steps

### Step 1: Check if Migrations Are Run

In **Laravel Cloud Console**, run:

```bash
php artisan migrate:status
```

**Expected:** All migrations should show "Ran"

**If migrations are missing:** Run:
```bash
php artisan migrate --force
```

---

### Step 2: Verify Database Connection

In **Laravel Cloud Console**, run:

```bash
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit
```

**Expected:** Should return connection info without errors

**If error:** Check environment variables in Laravel Cloud:
- `DB_HOST` (must be from Laravel Cloud Infrastructure, NOT `127.0.0.1`)
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

---

### Step 3: Check if Tables Exist

In **Laravel Cloud Console**, run:

```bash
php artisan tinker
>>> Schema::hasTable('employees')
>>> Schema::hasTable('projects')
>>> Schema::hasTable('internet_services')
>>> Schema::hasTable('entity_budgets')
>>> exit
```

**Expected:** All should return `true`

**If any return `false`:** Run migrations:
```bash
php artisan migrate --force
```

---

### Step 4: Clear All Caches

In **Laravel Cloud Console**, run:

```bash
php artisan optimize:clear
```

This clears:
- Config cache
- Route cache
- View cache
- Application cache

---

## 🎯 Most Likely Solution

**90% of the time, it's one of these:**

1. **Migrations not run** → Run `php artisan migrate --force`
2. **Wrong database connection** → Check `DB_HOST` in environment variables
3. **Cache issue** → Run `php artisan optimize:clear`

---

## 📋 Complete Checklist

Run these commands in Laravel Cloud Console (in order):

```bash
# 1. Check migrations
php artisan migrate:status

# 2. If needed, run migrations
php artisan migrate --force

# 3. Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit

# 4. Check tables
php artisan tinker
>>> Schema::hasTable('employees')
>>> Schema::hasTable('projects')
>>> exit

# 5. Clear caches
php artisan optimize:clear
```

---

## 🔍 If Still Not Working

### Check Laravel Cloud Logs

1. Go to: Your Site → **main** → **Logs** tab
2. Look for recent errors
3. Copy the exact error message

### Common Issues:

**Error: "Connection refused"**
- Check `DB_HOST` in environment variables
- Must be from Laravel Cloud Infrastructure (not `127.0.0.1`)

**Error: "Table doesn't exist"**
- Run: `php artisan migrate --force`

**Error: "Access denied"**
- Check `DB_USERNAME` and `DB_PASSWORD` in environment variables

---

## ✅ After Fixing

1. **Test the page again:**
   - Visit: `https://asset-management.laravel.cloud/internet-services/create`
   - Visit: `/projects/create`
   - Should load without errors

2. **Verify other pages work:**
   - `/internet-services/create`
   - `/location-master`
   - `/location-assets`
   - `/asset-transactions/create`
   - `/entity-budget/create`

---

## 🚀 Quick Command Summary

**Most common fix (run this first):**

```bash
php artisan migrate --force
php artisan optimize:clear
```

Then test the page again!

---

## 🔍 Internet Service error on `/internet-services/create`

If you see **"Database error occurred. On production run: php artisan migrate --force"** on the Internet Service page:

### Step 1: Diagnose (optional)

Run this in production to see what’s missing:

```bash
php artisan internet-service:check
```

It will report:
- Database connection
- Whether `internet_services`, `projects`, `employees` tables exist
- Whether `internet_services` has all required columns

### Step 2: Apply fix

```bash
php artisan migrate --force
php artisan optimize:clear
```

### Step 3: If it still fails

Check **application logs** (Laravel Cloud → your app → Logs). The exact SQL error is logged there. Then either:
- Fix the schema (e.g. run missing migrations), or
- Share the log line so the code can be adjusted.
