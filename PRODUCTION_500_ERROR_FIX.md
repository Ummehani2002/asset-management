# 🔧 Fixing 500 Error in Production (Location Master)

## The Problem
- ✅ Works locally (`final_asset.test/location-master`)
- ❌ 500 error in production (`asset-mgmt.laravel.cloud/location-master`)

## 🔍 Step 1: Check Production Logs

### In Laravel Cloud:
1. Go to: https://cloud.laravel.com
2. Click on your site: **asset-mgmt**
3. Click on environment: **main**
4. Click **"Logs"** tab
5. Look for recent errors related to `/location-master`

### What to Look For:
- `Database connection failed`
- `locations table does not exist`
- `SQLSTATE[HY000]` errors
- `Class not found` errors
- `Call to undefined method` errors

---

## ✅ Step 2: Most Common Fixes

### Fix 1: Run Migrations (Most Common)
**If you see "table does not exist" errors:**

1. In Laravel Cloud → Your Site → **main** environment
2. Click **"Commands"** or **"Console"** tab
3. Run:
   ```bash
   php artisan migrate --force
   ```
4. Wait for completion
5. Verify:
   ```bash
   php artisan migrate:status
   ```

### Fix 2: Check Database Connection
**If you see "Connection refused" or "Connection failed":**

1. Go to **Environment Variables** in Laravel Cloud
2. Verify these are set correctly:
   ```
   DB_CONNECTION=mysql
   DB_HOST=your-actual-host-from-laravel-cloud  # NOT 127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your-database-name
   DB_USERNAME=your-username
   DB_PASSWORD=your-password
   ```

3. **Important:** `DB_HOST` must be from Laravel Cloud Infrastructure, not `127.0.0.1`

4. Test connection in Console:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```
   Should return connection info without errors.

### Fix 3: Clear All Caches
**If code changes aren't taking effect:**

In Laravel Cloud Console, run:
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Fix 4: Check PHP Version
**If you see "Class not found" or syntax errors:**

1. In Laravel Cloud → Your Site → Settings
2. Verify PHP version is **8.2 or higher**
3. If lower, upgrade PHP version

### Fix 5: Verify Environment Variables
**Check these critical variables are set:**

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-key-here
APP_URL=https://asset-mgmt.laravel.cloud

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=your-db-name
DB_USERNAME=your-username
DB_PASSWORD=your-password

SESSION_DRIVER=database
```

---

## 🔍 Step 3: Debug in Production Console

### Check Database Connection:
```bash
php artisan tinker
>>> DB::connection()->getPdo();
>>> DB::select('SELECT DATABASE()');
```

### Check if Tables Exist:
```bash
php artisan tinker
>>> Schema::hasTable('locations');
>>> DB::select('SHOW TABLES');
```

### Check Recent Logs:
```bash
tail -n 50 storage/logs/laravel.log
```

### Test Location Model:
```bash
php artisan tinker
>>> \App\Models\Location::count();
```

---

## 📋 Quick Checklist

- [ ] Migrations run: `php artisan migrate:status` shows all "Ran"
- [ ] Database connection works: `DB::connection()->getPdo()` succeeds
- [ ] Tables exist: `Schema::hasTable('locations')` returns `true`
- [ ] Environment variables set correctly
- [ ] Caches cleared
- [ ] PHP version is 8.2+
- [ ] Checked Laravel Cloud logs for specific error

---

## 🚨 If Still Getting 500 Error

1. **Enable Debug Mode Temporarily:**
   - In Laravel Cloud → Environment Variables
   - Set: `APP_DEBUG=true`
   - This will show the actual error instead of generic 500 page
   - **Remember to set back to `false` after fixing!**

2. **Check Logs Again:**
   - With debug enabled, the error will be more detailed
   - Copy the full error message
   - Fix the specific issue

3. **Common Production-Specific Issues:**
   - Missing Composer dependencies → Run `composer install --no-dev --optimize-autoloader`
   - File permissions → Laravel Cloud handles this automatically
   - Missing storage link → Run `php artisan storage:link`
   - Memory limit → Usually not an issue on Laravel Cloud

---

## 📞 Still Need Help?

If the error persists after trying all above:

1. Copy the exact error from Laravel Cloud logs
2. Note what you've already tried
3. Check if other pages work (Dashboard, Employee Master, etc.)
4. Compare working pages with Location Master to find differences

---

## ✅ After Fixing

Once the page loads:
1. Set `APP_DEBUG=false` back in environment variables
2. Clear caches again
3. Test the page functionality
4. Monitor logs for any new errors
