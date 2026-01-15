# 🗑️ Clear Database Data - Quick Guide

## Method 1: Using HeidiSQL (Fastest)

1. **In HeidiSQL:**
   - Select your database: `asset_managementsystem`
   - Right-click on database name
   - Click **"Empty Database"**
   - Confirm when prompted

   **OR**

   - Select all tables (Ctrl+A)
   - Right-click → **"Truncate"**
   - Confirm

2. **Done!** All data is cleared, tables remain intact.

---

## Method 2: Using Artisan Command

Open terminal in your project folder and run:

```bash
php artisan data:clear
```

When prompted, type: `yes`

This will:
- ✅ Clear all data from all tables
- ✅ Preserve table structure
- ✅ Skip system tables (migrations, sessions, etc.)

---

## Method 3: Manual SQL in HeidiSQL

1. Open **Query** tab in HeidiSQL
2. Paste this SQL:

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

SET FOREIGN_KEY_CHECKS=1;
```

3. Click **"Execute"** (F9)

---

## ✅ After Clearing

Your database is now empty and ready for new data!

- Tables structure is preserved
- You can start creating new records
- All auto-increment IDs will reset
