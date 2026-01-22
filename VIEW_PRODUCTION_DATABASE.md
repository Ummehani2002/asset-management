

### Step 1: Access Laravel Cloud Console

1. Go to: **https://cloud.laravel.com**
2. Click on your site: **asset-management**
3. Click on environment: **main** (blue square)
4. Click **"Commands"** tab

### Step 2: Use Tinker to View Data

Run this command:

```bash
php artisan tinker
```

Then you can query your database:

```php
// View all employees
DB::table('employees')->get();

// Count records
DB::table('employees')->count();
DB::table('assets')->count();
DB::table('locations')->count();

// View specific record
DB::table('employees')->where('id', 1)->first();

// List all tables
DB::select('SHOW TABLES');

// View table structure
DB::select('DESCRIBE employees');

// Exit tinker
exit
```

---

## Method 2: Get Database Credentials (For External Tools)

### Step 1: Get Database Connection Info

1. Go to: **https://cloud.laravel.com**
2. Your site → **main** environment
3. Click **"Settings"** tab
4. Click **"Environment Variables"**
5. Look for these variables:
   - `DB_HOST` (e.g., `db.xxxxx.laravel.cloud`)
   - `DB_DATABASE` (your database name)
   - `DB_USERNAME` (your database username)
   - `DB_PASSWORD` (your database password)
   - `DB_PORT` (usually `3306`)

### Step 2: Alternative - Check Infrastructure Tab

1. Go to: **https://cloud.laravel.com**
2. Your site → **main** environment
3. Click **"Infrastructure"** tab
4. Look for your database service
5. Click on it to see connection details

---

## Method 3: Connect Using HeidiSQL (Recommended for GUI)

### Step 1: Get Database Credentials

Follow **Method 2** above to get:
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_PORT` (usually `3306`)

### Step 2: Open HeidiSQL

1. Open **HeidiSQL** on your computer
2. Click **"New"** (or press `Ctrl+N`)

### Step 3: Enter Connection Details

Fill in the connection form:

```
Network type: MySQL (TCP/IP)
Hostname / IP: [paste DB_HOST from Laravel Cloud]
User: [paste DB_USERNAME]
Password: [paste DB_PASSWORD]
Port: 3306 (or the port from DB_PORT)
```

### Step 4: Connect

1. Click **"Open"** button
2. Select your database from the left sidebar
3. You can now browse all tables and data!

---

## Method 4: Connect Using MySQL Workbench

### Step 1: Get Database Credentials

Follow **Method 2** above to get your credentials.

### Step 2: Create New Connection

1. Open **MySQL Workbench**
2. Click **"+"** next to "MySQL Connections"
3. Enter connection details:
   - **Connection Name:** Production Database
   - **Hostname:** [paste DB_HOST]
   - **Port:** 3306 (or from DB_PORT)
   - **Username:** [paste DB_USERNAME]
   - **Password:** [paste DB_PASSWORD]

### Step 3: Connect

1. Click **"Test Connection"** to verify
2. Click **"OK"** to save
3. Double-click the connection to connect
4. Browse your database!

---

## Method 5: View Data via SQL Commands in Laravel Cloud

### Step 1: Go to Commands Tab

1. Laravel Cloud → Your site → **main** → **Commands** tab

### Step 2: Run SQL Queries

You can run SQL directly using tinker:

```bash
php artisan tinker
```

Then:

```php
// View all employees
DB::select('SELECT * FROM employees');

// View all assets
DB::select('SELECT * FROM assets LIMIT 10');

// Count records in each table
DB::select('SELECT 
    (SELECT COUNT(*) FROM employees) as employees,
    (SELECT COUNT(*) FROM assets) as assets,
    (SELECT COUNT(*) FROM locations) as locations,
    (SELECT COUNT(*) FROM projects) as projects');

// View specific employee with details
DB::select('SELECT * FROM employees WHERE id = 1');

// Exit
exit
```

---

## Method 6: Export Database to View Locally

### Step 1: Export from Production

In Laravel Cloud → Commands tab:

```bash
php artisan tinker
```

Then:

```php
// This will create a file (if you have write access)
// Or use mysqldump if available
exit
```

**OR** use HeidiSQL (Method 3) to export:
1. Right-click on database
2. Select **"Export database as SQL"**
3. Save to your computer
4. Import into local database to view

---

## 🔍 Quick Database Inspection Commands

Run these in Laravel Cloud → Commands → Tinker:

```php
// 1. Check connection
DB::connection()->getPdo();

// 2. See which database you're connected to
DB::select('SELECT DATABASE()');

// 3. List all tables
DB::select('SHOW TABLES');

// 4. Count records in main tables
echo "Employees: " . DB::table('employees')->count() . "\n";
echo "Assets: " . DB::table('assets')->count() . "\n";
echo "Locations: " . DB::table('locations')->count() . "\n";
echo "Projects: " . DB::table('projects')->count() . "\n";

// 5. View table structure
DB::select('DESCRIBE employees');

// 6. View recent records
DB::table('employees')->latest()->limit(5)->get();

exit
```

---

## ⚠️ Important Notes

### Security
- **Never share** your database credentials
- **Use strong passwords** (Laravel Cloud generates these)
- **Only connect** from trusted networks if possible

### Connection Issues
- If connection fails, check:
  - `DB_HOST` is correct (not `127.0.0.1`)
  - Firewall allows connections from your IP
  - Database is running (check Infrastructure tab)

### Best Practice
- **Use HeidiSQL or MySQL Workbench** for easy browsing
- **Use Tinker** for quick queries
- **Export backups** before making changes

---

## 📋 Quick Reference

| Method | Best For | Difficulty |
|--------|----------|------------|
| **Tinker** | Quick queries, testing | Easy |
| **HeidiSQL** | Browsing data, GUI | Easy |
| **MySQL Workbench** | Advanced queries, GUI | Medium |
| **SQL Commands** | Direct SQL access | Medium |

---

## ✅ Recommended Approach

**For daily use:** Use **HeidiSQL** (Method 3) - it's the easiest way to browse and view your production database with a friendly interface.

**For quick checks:** Use **Tinker** (Method 1) - fastest way to run simple queries.

---

## 🆘 Troubleshooting

### Can't Connect from HeidiSQL - "Access denied" or "ProxySQL Error"

**This is common with Laravel Cloud databases!** They use ProxySQL and may restrict external connections.

#### Solution 1: Use Laravel Cloud Console Instead (Recommended)

Laravel Cloud databases are designed to be accessed **from within the Laravel Cloud environment**, not directly from external tools like HeidiSQL.

**Best approach:** Use **Tinker** (Method 1) or **Laravel Cloud's built-in database viewer** if available.

#### Solution 2: Check if Direct Connection is Allowed

1. **Verify credentials are correct:**
   - Go to Laravel Cloud → Settings → Environment Variables
   - Double-check `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`
   - Make sure there are no extra spaces or characters

2. **Check Infrastructure tab:**
   - Go to Laravel Cloud → Infrastructure tab
   - Look for your database service
   - Check if it shows "External connections" or "Public access"
   - Some Laravel Cloud databases are **internal-only** (not accessible from outside)

3. **Try using the exact hostname:**
   - Don't use `127.0.0.1` or `localhost`
   - Use the exact `DB_HOST` value from environment variables
   - It should look like: `db.xxxxx.laravel.cloud` or similar

#### Solution 3: Use SSH Tunnel (Advanced)

If Laravel Cloud supports SSH access, you can create an SSH tunnel:

1. **Check if SSH access is available:**
   - Laravel Cloud → Your site → Settings
   - Look for SSH access or terminal access

2. **Create SSH tunnel:**
   ```bash
   ssh -L 3306:localhost:3306 user@your-laravel-cloud-host
   ```

3. **Connect HeidiSQL to `127.0.0.1:3306`** (through the tunnel)

#### Solution 4: Export Data via Laravel Cloud Console

Since direct connection might not work, export data instead:

1. **In Laravel Cloud → Commands tab:**
   ```bash
   php artisan tinker
   ```

2. **Export data:**
   ```php
   // Get all employees
   $employees = DB::table('employees')->get();
   echo json_encode($employees, JSON_PRETTY_PRINT);
   
   // Or export to file (if you have write access)
   exit
   ```

3. **Or use mysqldump if available:**
   ```bash
   mysqldump -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > backup.sql
   ```

#### Solution 5: Contact Laravel Cloud Support

If you need direct database access:
- Laravel Cloud databases are often **internal-only** for security
- Contact Laravel Cloud support to enable external connections (if available)
- They may require IP whitelisting or VPN access

---

### Alternative: Use Laravel Cloud's Database Viewer

Some Laravel Cloud plans include a built-in database viewer:
1. Go to Laravel Cloud → Your site → **main**
2. Look for **"Database"** or **"Data"** tab
3. This provides a web-based interface to browse your database

---

### Recommended Workflow

**Since direct HeidiSQL connection may not work:**

1. ✅ **For viewing data:** Use **Tinker** (Method 1) - it always works
2. ✅ **For browsing:** Use Laravel Cloud's built-in database viewer (if available)
3. ✅ **For exports:** Use `mysqldump` via Laravel Cloud Console
4. ✅ **For local testing:** Export production data, then import to local database

### Tinker Not Working

1. Make sure you're in the correct environment (`main`)
2. Check if database connection is working:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```
3. If error, check environment variables

### Can't See Tables

1. Run migrations: `php artisan migrate --force`
2. Check if tables exist:
   ```php
   DB::select('SHOW TABLES');
   ```

---

## 🎯 Next Steps

Once you can view your database:
- ✅ Browse your data
- ✅ Verify data was cleared (if you ran `data:clear`)
- ✅ Check table structures
- ✅ Export backups before making changes
