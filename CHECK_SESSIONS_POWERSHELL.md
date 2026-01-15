# ✅ Check Sessions Table - PowerShell Commands

## PowerShell Quote Escaping Issue

PowerShell handles quotes differently than bash. Use these commands:

---

## ✅ Option 1: Simple Check (Recommended)

```powershell
php artisan tinker --execute='echo Schema::hasTable("sessions") ? "EXISTS" : "MISSING";'
```

**Or use single quotes for the outer string:**

```powershell
php artisan tinker --execute='echo Schema::hasTable(''sessions'') ? ''EXISTS'' : ''MISSING'';'
```

---

## ✅ Option 2: Using DB Directly

```powershell
php artisan tinker --execute='$result = DB::select("SHOW TABLES LIKE ''sessions''"); echo count($result) > 0 ? "EXISTS" : "MISSING";'
```

---

## ✅ Option 3: Create a PHP Script (Easiest)

Create a temporary file `check_sessions.php`:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

echo Schema::hasTable('sessions') ? "Sessions table EXISTS ✅\n" : "Sessions table MISSING ❌\n";
```

Then run:
```powershell
php check_sessions.php
```

---

## ✅ Option 4: Use Artisan Command (If Available)

```powershell
php artisan db:show
```

This lists all tables. Look for `sessions` in the output.

---

## 🎯 Quick PowerShell Commands

### Check if sessions table exists:
```powershell
php artisan tinker --execute='var_dump(Schema::hasTable("sessions"));'
```

### List all tables:
```powershell
php artisan tinker --execute='print_r(DB::select("SHOW TABLES"));'
```

### Count sessions table rows (if exists):
```powershell
php artisan tinker --execute='try { echo DB::table("sessions")->count(); } catch (Exception $e) { echo "Table does not exist"; }'
```

---

## 🔧 If Sessions Table is Missing

Run migrations:

```powershell
php artisan migrate --force
```

---

## ✅ Verify After Creating

```powershell
php artisan tinker --execute='echo Schema::hasTable("sessions") ? "EXISTS ✅" : "MISSING ❌";'
```

---

## 🚀 Complete Fix Process (PowerShell)

```powershell
# 1. Check if sessions table exists
php artisan tinker --execute='echo Schema::hasTable("sessions") ? "EXISTS" : "MISSING";'

# 2. If it says MISSING, run migrations
php artisan migrate --force

# 3. Verify it was created
php artisan tinker --execute='echo Schema::hasTable("sessions") ? "EXISTS ✅" : "MISSING ❌";'

# 4. Clear caches
php artisan optimize:clear
```

---

## 💡 PowerShell Tip

In PowerShell, you can also use backticks to escape:

```powershell
php artisan tinker --execute="echo Schema::hasTable(`"sessions`") ? `"EXISTS`" : `"MISSING`";"
```

But the single-quote method is simpler!
