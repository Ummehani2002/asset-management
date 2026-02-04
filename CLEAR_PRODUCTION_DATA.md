# 🗑️ Clear Production Data - Step by Step

## ⚠️ IMPORTANT: This will DELETE ALL DATA in Production!

Make sure you want to do this before proceeding.

---

## Method 1: Using Artisan Command (Recommended)

### Step 1: Go to Laravel Cloud

1. Visit: **https://cloud.laravel.com**
2. Click on your site: **asset-management**
3. Click on environment: **main** (blue square)
4. Click **"Commands"** tab (or **"Console"** tab)

### Step 2: Run Clear Command

In the command prompt, type:

```bash
php artisan data:clear
```

Press Enter.

### Step 3: Confirm

When you see:
```
⚠️  WARNING: This will delete ALL data from all tables. Are you sure? (yes/no) [no]:
```

Type: **`yes`** and press Enter.

### Step 4: Wait for Completion

You'll see output like:
```
✅ Cleared: employees
✅ Cleared: locations
✅ Cleared: assets
...
✅ Data clearing completed!
Tables cleared: XX
Tables skipped: X
```

---

## Method 2: Using SQL Directly (Alternative)

If the command doesn't work, you can use SQL:

### Step 1: Go to Laravel Cloud Console

1. Go to **Commands** tab
2. Click **"Open Console"** or use the terminal

### Step 2: Run SQL Commands

Type this SQL (one line at a time or all together):

```sql
SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE employees;
TRUNCATE TABLE locations;
TRUNCATE TABLE assets;
TRUNCATE TABLE asset_categories;
TRUNCATE TABLE brands;
TRUNCATE TABLE projects;
TRUNCATE TABLE asset_transactions;
TRUNCATE TABLE entity_budgets;
TRUNCATE TABLE budget_expenses;
TRUNCATE TABLE time_managements;
TRUNCATE TABLE internet_services;
TRUNCATE TABLE issue_notes;
TRUNCATE TABLE category_features;
TRUNCATE TABLE category_feature_values;
TRUNCATE TABLE maintenance_budgets;
TRUNCATE TABLE maintenance_expenses;
TRUNCATE TABLE datacard_transactions;
TRUNCATE TABLE preventive_maintenances;

SET FOREIGN_KEY_CHECKS=1;
```

---

## Method 3: Verify Data is Cleared

After clearing, verify by running:

```bash
php artisan tinker
```

Then type:
```php
DB::table('employees')->count();
DB::table('assets')->count();
DB::table('asset_categories')->count();
exit
```

All should return `0`.

---

## ⚠️ Troubleshooting

### Command Not Found

If `php artisan data:clear` doesn't work:

1. Make sure you're in the correct environment
2. Try: `php artisan list` to see available commands
3. If command doesn't exist, use Method 2 (SQL) instead

### Foreign Key Errors

If you get foreign key errors:

1. Use the SQL method (Method 2)
2. The `SET FOREIGN_KEY_CHECKS=0;` disables foreign key checks
3. Then re-enable with `SET FOREIGN_KEY_CHECKS=1;`

### Still Seeing Data

If you still see data after clearing:

1. **Clear browser cache** - Press Ctrl+F5 to hard refresh
2. **Check you're on production** - URL should be `asset-mgmt.laravel.cloud`
3. **Wait a few seconds** - Sometimes cache needs to clear
4. **Run optimize:clear**:
   ```bash
   php artisan optimize:clear
   ```

---

## ✅ After Clearing

1. **Refresh your browser** (Ctrl+F5)
2. **Check dashboard** - Should show empty/zero counts
3. **Start entering new data** - Your team can now add actual data

---

## 📋 Quick Checklist

- [ ] Go to Laravel Cloud → Commands tab
- [ ] Run: `php artisan data:clear`
- [ ] Type `yes` when prompted
- [ ] Wait for completion message
- [ ] Verify data is cleared (check dashboard)
- [ ] Clear browser cache (Ctrl+F5)
- [ ] Ready for new data!
