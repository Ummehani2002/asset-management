# How to Delete Budget Expense and Maintenance Data in Production

Run these in **Laravel Cloud Console** (or SSH into production) from your project root.

---

## Option 1: Using Laravel Tinker (recommended)

```bash
php artisan tinker
```

Then run the commands below **one at a time** inside tinker.

### 1. Delete all budget expenses

```php
// Count first (optional)
\App\Models\BudgetExpense::count();

// Delete all budget expense records
\App\Models\BudgetExpense::query()->delete();

// Verify
\App\Models\BudgetExpense::count();
```

**Note:** This only deletes from `budget_expenses`. It does **not** delete `entity_budgets` (the budget headers/amounts). Entity budgets stay; only the expense lines are removed.

To also reset entity budget amounts (e.g. set budget_2026 back to 0) you would need to update those rows; ask if you need that.

---

### 2. Delete maintenance-related data

**Maintenance assignments** (links maintenance transactions to assigned staff):

```php
// Count first (optional)
\DB::table('maintenance_assignments')->count();

// Delete all
\DB::table('maintenance_assignments')->delete();
```

**Asset transactions that are maintenance type** (optional – only if you want to remove maintenance transaction history):

```php
// Count maintenance transactions (optional)
\App\Models\AssetTransaction::where('transaction_type', 'system_maintenance')->count();

// Delete only maintenance transactions
\App\Models\AssetTransaction::where('transaction_type', 'system_maintenance')->delete();
```

**Preventive maintenance** (if the table exists):

```php
if (\Illuminate\Support\Facades\Schema::hasTable('preventive_maintenance')) {
    \DB::table('preventive_maintenance')->delete();
}
```

**Maintenance expenses / maintenance budgets** (if you use these tables and want them empty):

```php
if (\Illuminate\Support\Facades\Schema::hasTable('maintenance_expenses')) {
    \DB::table('maintenance_expenses')->delete();
}
if (\Illuminate\Support\Facades\Schema::hasTable('maintenance_budgets')) {
    \DB::table('maintenance_budgets')->delete();
}
```

Then exit tinker:

```php
exit
```

---

## Option 2: One-off Artisan command (optional)

You can also add a custom command (e.g. `php artisan data:clear-budget-expenses`) that runs the same logic. Use the code above inside the command.

---

## Summary – copy/paste in tinker

**Budget expenses only:**

```php
\App\Models\BudgetExpense::query()->delete();
```

**Maintenance assignments only:**

```php
\DB::table('maintenance_assignments')->delete();
```

**Maintenance transactions only:**

```php
\App\Models\AssetTransaction::where('transaction_type', 'system_maintenance')->delete();
```

**Order if deleting both budget and maintenance:**  
Run budget expense delete and maintenance deletes in any order; no dependency between them. If you delete maintenance, do `maintenance_assignments` before deleting `asset_transactions` (maintenance type), because assignments reference `asset_transaction_id`.

---

## Safety

- **Back up** the database before bulk deletes in production.
- Run `->count()` first to confirm how many rows will be removed.
- There is **no undo**; deleted rows are gone unless you restore from backup.
