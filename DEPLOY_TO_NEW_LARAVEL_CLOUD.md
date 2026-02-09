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

# Mail (optional - for job delay alerts, notifications)
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

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
- Go to **Employee Master** → **Import Employees**  
- Upload your CSV (Excel saved as CSV UTF-8)

**Other data:**  
- Enter manually, or  
- Use `php artisan data:clear --keep-users` if you need to reset and re-import

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
