# 🚀 Deploy to a New Laravel Cloud Account


---

## Step 3: Add Database

1. **Create database:**
   - Go to **Add-ons** or ** Databases**
   - Click **"Add Database"** (MySQL)
   - Choose plan and region
   - Database will be provisioned and credentials added to environment variables

2. **Confirm variables:**
   - Go to **Environment** → **Variables**
   - Ensure `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` are set

---

## Step 4: Configure Environment Variables

In **Environment** → **Variables**, set:

```
APP_NAME="Asset Management System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-SITE-NAME.laravel.cloud

# Generate a new key after first deploy, or run: php artisan key:generate --show
APP_KEY=base64:your-key-here

# Database (auto-filled if you added a database add-on)
DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

# Mail (required for maintenance approval emails – see MAIL_PRODUCTION.md)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD="your app password"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

See **MAIL_PRODUCTION.md** for a full checklist and troubleshooting (Gmail App Password, testing, logs).

**Important:** Create a new `APP_KEY` for this deployment:
```bash
php artisan key:generate --show
```
Copy the output and set it as `APP_KEY` in Laravel Cloud.

---

## Step 5: Deployment Configuration

1. **Build commands** (Laravel Cloud usually auto-detects, but verify):
   - Go to **Settings** → **Build & Deploy**
   - Build command: `composer install --no-dev --optimize-autoloader && npm ci && npm run build`
   - Or leave default if it works

2. **Deploy commands** (run after each deploy):
   - In **Settings** → **Deploy Commands** or **Console**, you may add:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=EntitySeeder --force
   php artisan storage:link
   php artisan optimize
   ```

---

## Step 6: First Deployment

1. **Trigger deploy:**
   - Push to your `main` branch, or
   - Go to **Deployments** → **Deploy now**

2. **Wait for build and deploy** (a few minutes)

3. **Run migrations in Console:**
   - Go to **Commands** or **Console**
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=EntitySeeder --force
   php artisan storage:link
   php artisan optimize:clear
   php artisan optimize
   ```

---

## Step 7: Create Admin User

After deployment, create an admin user:

```bash
php artisan tinker
```

Then in tinker:
```php
\App\Models\User::create([
    'name' => 'Admin',
    'username' => 'admin',
    'email' => 'admin@yourcompany.com',
    'password' => bcrypt('your-secure-password'),
    'role' => 'admin'
]);
exit
```

---

## Step 8: Import Data (Optional)

**Employees:**  
- Go to **Employee Master** → **Import Employees** (or **Entity Master** → **Import Employees**)
- Upload your CSV (Excel saved as CSV UTF-8)

**Entities:**  
- Go to **Entity Master** → **Import Entities** → choose From CSV or From Existing Employees

**Other data:**  
- Enter manually, or  
- Use `php artisan data:clear --keep-users` if you need to reset and re-import

---

## Step 9: Enabling Import in Production (Laravel Cloud)

If import works on localhost but fails in production:

### 1. Deploy the latest code
```bash
git add .
git commit -m "Add employee and entity import"
git push origin main
```
Trigger a new deployment in Laravel Cloud.

### 2. Check PHP upload limits
Large CSVs may fail if `upload_max_filesize` or `post_max_size` are too low. In **Laravel Cloud**:

- Go to **Environment** → **Variables**
- Add (if supported): `PHP_INI_SCAN_DIR` or use the Console to check current limits:
  ```bash
  php -i | grep -E "upload_max_filesize|post_max_size"
  ```

### 3. Create `.user.ini` for upload limits (if hosting supports it)
Create a file `.user.ini` in your project root:
```ini
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 120
```
Commit and deploy. (Note: Laravel Cloud may use Nixpacks; if `.user.ini` doesn't work, contact Laravel Cloud support for PHP config options.)

### 4. Common production issues
| Issue | Fix |
|-------|-----|
| 413 Payload Too Large | Increase `post_max_size` and `upload_max_filesize` |
| 504 Gateway Timeout | Increase `max_execution_time`; split large imports into smaller files |
| "Could not read file" | Temp directory permissions; try smaller file |
| Import button missing | Ensure latest code is deployed; clear browser cache |

---

## ✅ Post-Deployment Checklist

- [ ] Application deploys successfully
- [ ] Environment variables set (especially `APP_KEY`, `APP_URL`, `DB_*`)
- [ ] Migrations run (`php artisan migrate --force`)
- [ ] EntitySeeder run (`php artisan db:seed --class=EntitySeeder --force`)
- [ ] Storage link created (`php artisan storage:link`)
- [ ] Admin user created
- [ ] Login works
- [ ] Caches cleared and optimized (`php artisan optimize:clear` then `php artisan optimize`)

---

## 🔗 Your New URL

After deployment, your app will be at:
```
https://YOUR-SITE-NAME.laravel.cloud
```
(Replace `YOUR-SITE-NAME` with the name you chose.)

---

## 📞 Troubleshooting

| Issue | Action |
|------|--------|
| 500 error | Set `APP_DEBUG=true` temporarily, check **Logs**, fix error, set `APP_DEBUG=false` |
| Database connection failed | Verify `DB_*` variables in **Environment** |
| Missing tables | Run `php artisan migrate --force` |
| Blank page | Run `php artisan optimize:clear` |
| Storage files 404 | Run `php artisan storage:link` |

---

## 🎯 Quick Command Reference

```bash
# In Laravel Cloud Console
php artisan migrate --force
php artisan db:seed --class=EntitySeeder --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

---

Ready to deploy to your new Laravel Cloud account.
