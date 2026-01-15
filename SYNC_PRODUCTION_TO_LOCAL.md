# 🔄 Sync Production Data to Local Database

## Overview
This guide helps you clear your local database and import production data.

---

## Method 1: Using Laravel Cloud Console (Recommended)

### Step 1: Export Data from Production

1. **Go to Laravel Cloud Console**
   - Visit: https://cloud.laravel.com
   - Navigate to your site → `main` environment
   - Click **"Commands"** tab

2. **Export Database**
   ```bash
   mysqldump -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > production_backup.sql
   ```
   
   **OR use Laravel's built-in export:**
   ```bash
   php artisan db:export production_backup.sql
   ```

3. **Download the file**
   - The file will be in your Laravel Cloud storage
   - Download it to your local machine

---

### Step 2: Clear Local Database

**Option A: Using HeidiSQL (GUI)**

1. Open HeidiSQL
2. Connect to your local database
3. Select your database
4. Right-click on database → **"Empty Database"** or **"Drop Database"**
5. Recreate database (if dropped)
6. Run migrations:
   ```bash
   php artisan migrate
   ```

**Option B: Using Artisan Command**

1. Open terminal in your project directory
2. Make sure `.env` points to local database
3. Run:
   ```bash
   php artisan data:clear
   ```
4. Confirm when prompted

---

### Step 3: Import Production Data to Local

**Option A: Using HeidiSQL**

1. Open HeidiSQL
2. Connect to your local database
3. Select your database
4. Click **"File"** → **"Load SQL file"**
5. Select the `production_backup.sql` file
6. Click **"Execute"**
7. Wait for import to complete

**Option B: Using Command Line**

1. Open terminal in your project directory
2. Make sure `.env` points to local database
3. Run:
   ```bash
   mysql -u your_local_user -p your_local_database < production_backup.sql
   ```

---

## Method 2: Direct Database Connection (Advanced)

### Step 1: Get Production Database Credentials

From Laravel Cloud → Settings → Environment Variables:
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

### Step 2: Connect HeidiSQL to Production

1. Open HeidiSQL
2. Create new session
3. Enter production credentials:
   - **Network type:** MySQL (TCP/IP)
   - **Hostname/IP:** `DB_HOST` value
   - **User:** `DB_USERNAME` value
   - **Password:** `DB_PASSWORD` value
   - **Database:** `DB_DATABASE` value
4. Test connection
5. Save session

### Step 3: Export from Production

1. Connect to production database in HeidiSQL
2. Select your database
3. Right-click database → **"Export database as SQL"**
4. Choose export options:
   - ✅ Structure
   - ✅ Data
   - ❌ Drop statements (optional)
5. Save file as `production_backup.sql`

### Step 4: Clear Local Database

1. Connect to local database in HeidiSQL
2. Select your database
3. Right-click database → **"Empty Database"**
4. Or run: `php artisan data:clear`

### Step 5: Import to Local

1. Connect to local database in HeidiSQL
2. Select your database
3. Click **"File"** → **"Load SQL file"**
4. Select `production_backup.sql`
5. Click **"Execute"**

---

## Method 3: Using Artisan Commands (Automated)

### Create Export Command

Run this in Laravel Cloud Console to export:
```bash
php artisan db:export production_backup.sql
```

### Clear Local

Run this locally:
```bash
php artisan data:clear
```

### Import Local

Run this locally:
```bash
php artisan db:import production_backup.sql
```

---

## ⚠️ Important Notes

1. **Backup First**: Always backup your local data before clearing
2. **Check .env**: Make sure local `.env` points to local database
3. **Migrations**: Run migrations on local before importing:
   ```bash
   php artisan migrate
   ```
4. **Foreign Keys**: The import might fail if foreign key constraints exist. Disable them temporarily:
   ```sql
   SET FOREIGN_KEY_CHECKS=0;
   -- Import data
   SET FOREIGN_KEY_CHECKS=1;
   ```

---

## Quick Checklist

- [ ] Export data from production
- [ ] Download backup file to local machine
- [ ] Clear local database (using HeidiSQL or `php artisan data:clear`)
- [ ] Run migrations on local (`php artisan migrate`)
- [ ] Import production backup to local
- [ ] Verify data in HeidiSQL
- [ ] Test application locally

---

## Troubleshooting

### Error: "Access denied"
- Check database credentials
- Verify user has proper permissions

### Error: "Table doesn't exist"
- Run migrations first: `php artisan migrate`

### Error: "Foreign key constraint fails"
- Disable foreign key checks during import
- Or import in correct order (parent tables first)

### Data not showing
- Clear cache: `php artisan optimize:clear`
- Check `.env` database connection
- Verify import completed successfully
