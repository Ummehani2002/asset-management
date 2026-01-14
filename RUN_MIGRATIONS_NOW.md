# 🚨 URGENT: Run Migrations in Laravel Cloud

## The Error You're Seeing

The error message **"Database table not found. Please run migrations: php artisan migrate --force"** means the database tables don't exist yet in your production environment.

**This is normal for a fresh deployment!** You just need to run migrations.

---

## ✅ Quick Fix - Run Migrations

### Step 1: Go to Laravel Cloud Console

1. Go to: https://cloud.laravel.com
2. Click on your site: **asset-management**
3. Click on your environment: **main** (the blue square icon)
4. Click on **"Commands"** tab (or **"Console"** tab)

### Step 2: Run Migrations

In the console/commands area, run:

```bash
php artisan migrate --force
```

**The `--force` flag is required in production.**

### Step 3: Wait for Completion

- You'll see output showing each migration running
- Wait until you see "Migration completed" or similar
- This usually takes 30-60 seconds

### Step 4: Verify

After migrations complete, run:

```bash
php artisan migrate:status
```

You should see all migrations marked as "Ran".

### Step 5: Test Again

Go back to your form and try creating an employee again. It should work now!

---

## 📋 What Migrations Will Create

The migrations will create these tables (and more):

- ✅ `users` (includes `sessions` table for success messages)
- ✅ `employees`
- ✅ `locations`
- ✅ `asset_categories`
- ✅ `brands`
- ✅ `assets`
- ✅ `projects`
- ✅ `time_managements`
- ✅ `internet_services`
- ✅ `issue_notes`
- ✅ `entity_budgets`
- ✅ And all other required tables

---

## ⚠️ Important Notes

1. **Don't run `migrate:refresh`** - This will DELETE all data!
2. **Use `migrate --force`** - Required in production
3. **Run once** - After migrations run, you won't need to run them again unless you add new migrations

---

## 🔍 If Migrations Fail

### Error: "Connection refused"
- Check database credentials in Laravel Cloud environment variables
- Verify database was created in Infrastructure section
- Ensure `DB_HOST` is correct (not `127.0.0.1`)

### Error: "Table already exists"
- Some tables might already exist
- This is okay, migrations will skip them
- Continue with the migration

### Error: "Access denied"
- Check database username and password
- Verify database user has proper permissions

---

## ✅ After Migrations Complete

1. **Refresh your browser**
2. **Try creating an employee again**
3. **You should see:**
   - ✅ Green success message: "Employee added successfully."
   - ✅ Employee appears in the list
   - ✅ No more error messages

---

## 🎯 Quick Command Reference

```bash
# Run migrations (DO THIS NOW)
php artisan migrate --force

# Check migration status
php artisan migrate:status

# Clear cache (if needed)
php artisan config:clear
php artisan cache:clear
```

---

## 📞 Still Having Issues?

If migrations fail or you see other errors:

1. **Check Laravel Cloud logs** - Look for detailed error messages
2. **Verify database connection** - Test in console:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```
3. **Check environment variables** - Ensure all database credentials are correct

---

**Once migrations are run, all your forms will work perfectly!** 🎉
