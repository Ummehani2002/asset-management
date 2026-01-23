# 🔐 How to Set Admin Access

## Quick Steps to Make a User Admin

### Option 1: Using Laravel Tinker (Recommended)

1. **Open terminal in your project directory:**
   ```bash
   cd d:\sites\final_asset
   ```

2. **Run Tinker:**
   ```bash
   php artisan tinker
   ```

3. **Find your user and set as admin:**
   ```php
   // List all users
   App\Models\User::all(['id', 'name', 'email', 'role']);
   
   // Set a specific user as admin (replace 'your-email@example.com' with your email)
   $user = App\Models\User::where('email', 'your-email@example.com')->first();
   $user->role = 'admin';
   $user->save();
   
   // Verify it worked
   $user->role; // Should return 'admin'
   
   // Exit tinker
   exit
   ```

### Option 2: Using Database Directly (HeidiSQL/phpMyAdmin)

1. **Open your database tool** (HeidiSQL, phpMyAdmin, etc.)

2. **Run this SQL query:**
   ```sql
   -- See all users
   SELECT id, name, email, username, role FROM users;
   
   -- Set a specific user as admin (replace 'your-email@example.com')
   UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
   
   -- Verify
   SELECT id, name, email, role FROM users WHERE role = 'admin';
   ```

### Option 3: Create a New Admin User

1. **Run Tinker:**
   ```bash
   php artisan tinker
   ```

2. **Create admin user:**
   ```php
   use Illuminate\Support\Facades\Hash;
   
   App\Models\User::create([
       'name' => 'Admin User',
       'username' => 'admin',
       'email' => 'admin@example.com',
       'password' => Hash::make('your-secure-password'),
       'role' => 'admin'
   ]);
   
   exit
   ```

---

## ✅ After Setting Admin Role

1. **Logout** from your current session (if logged in)
2. **Login again** with the admin user credentials
3. **You should now see:**
   - "Users" menu item in sidebar
   - Full access to Employee Master, Project Master, Location Master
   - Access to Asset & Brand Management
   - Access to Budget Maintenance
   - All admin-only features

---

## 🔍 Verify Admin Access

After logging in as admin, you should see these menu items in the sidebar:
- ✅ Users
- ✅ Employee Master (with "New Employee" option)
- ✅ Project Master (with "Create Project" option)
- ✅ Location Master (with "New Location" option)
- ✅ Asset & Brand (with "Asset Master" option)
- ✅ Budget Maintenance

If you don't see these, the role might not be set correctly. Check again using:
```php
php artisan tinker
>>> App\Models\User::where('email', 'your-email@example.com')->first()->role;
```

---

## ⚠️ Important Notes

- **Default Role:** All existing users have `role = 'user'` by default
- **At Least One Admin:** Make sure you set at least one user as admin
- **Security:** Keep admin credentials secure
- **Multiple Admins:** You can have multiple admin users
