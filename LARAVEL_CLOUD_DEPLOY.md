# 🚀 Laravel Cloud Deployment Guide

## ⚠️ QUICK FIX: "Connection Refused" Error

If you're seeing this error during deployment:
```
SQLSTATE[HY000] [2002] Connection refused
```

**Immediate Fix:**
1. **Create Database First:**
   - Go to Laravel Cloud → Your Site → Click on your environment (e.g., **main**)
   - Click on **"Infrastructure"** tab/section
   - Click **"Add database"** or **"Create Database"** (if not already created)
   - Wait for it to be fully created (1-2 minutes)

2. **Get Correct Database Host:**
   - In Infrastructure section, find your database
   - Click on the database to view details
   - Copy the **exact database host** shown
   - It will be something like `mysql-xxxx.laravel.cloud` (NOT `127.0.0.1`)

3. **Update Environment Variables:**
   - Go to **Environment Variables** tab (in your environment settings)
   - Update `DB_HOST` with the actual host from step 2
   - Update all other database credentials from the Infrastructure section

4. **Disable Auto-Migrations:**
   - Go to **Deploy Settings** or **Build Settings**
   - Remove `php artisan migrate --force` from deploy commands
   - Save and redeploy

5. **Run Migrations Manually:**
   - After successful deployment, go to **Console**
   - Run: `php artisan migrate --force`

---

## Prerequisites

Before deploying, ensure you have:
- [ ] Laravel Cloud account (already have it ✓)
- [ ] Code pushed to GitHub
- [ ] Gmail account (for email notifications)

---

## Step 1: Prepare Your Code

### 1.1 Generate APP_KEY (if not already done)
```bash
php artisan key:generate --show
```
**Copy the output** - you'll need it in Step 3!

Example: `base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

### 1.2 Push Code to GitHub

Make sure your code is pushed to GitHub:
```bash
git add .
git commit -m "Ready for Laravel Cloud deployment"
git push origin main
```

---

## Step 2: Connect Repository to Laravel Cloud

1. **Login to Laravel Cloud**
   - Go to [cloud.laravel.com](https://cloud.laravel.com)
   - Login with your account

2. **Create New Site**
   - Click **"New Site"** or **"Create Site"**
   - Select **"Deploy from GitHub"**
   - Authorize Laravel Cloud to access your GitHub account (if not already done)
   - Select your repository: `final_asset` (or your repo name)
   - Click **"Connect"**

3. **Configure Site**
   - **Site Name:** `final-asset` (or any name you prefer)
   - **PHP Version:** Select **PHP 8.2** or higher
   - **Region:** Choose closest to your users
   - Click **"Create Site"**

---

## Step 3: Configure Environment Variables

In Laravel Cloud dashboard → Your Site → **Environment Variables**, add:

### Application Settings:
```
APP_NAME=Tanseeq Asset Management
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:PASTE_YOUR_KEY_FROM_STEP_1
APP_URL=https://your-site-name.laravel.cloud
APP_TIMEZONE=Asia/Dubai
```

### Database Settings:
**IMPORTANT:** You must create the database FIRST in Laravel Cloud before setting these variables!

1. **Create Database First:**
   - In Laravel Cloud dashboard → Your Site → **Database** tab
   - Click **"Create Database"** or **"Add Database"**
   - Wait for database to be created (takes 1-2 minutes)
   - **Copy the database credentials** provided by Laravel Cloud

2. **Set Database Environment Variables:**
   Use the EXACT credentials provided by Laravel Cloud (NOT 127.0.0.1):
   ```
   DB_CONNECTION=mysql
   DB_HOST=YOUR_DB_HOST_FROM_LARAVEL_CLOUD  # NOT 127.0.0.1 - use the actual host provided
   DB_PORT=3306
   DB_DATABASE=YOUR_DB_NAME_FROM_LARAVEL_CLOUD
   DB_USERNAME=YOUR_DB_USERNAME_FROM_LARAVEL_CLOUD
   DB_PASSWORD=YOUR_DB_PASSWORD_FROM_LARAVEL_CLOUD
   ```

**Critical:** 
- The `DB_HOST` is NOT `127.0.0.1` - it's a specific host provided by Laravel Cloud
- Copy the credentials EXACTLY as shown in the Laravel Cloud database dashboard
- Database must be created BEFORE running migrations

### Email Settings (for notifications):
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME=Tanseeq Asset Management
```

**Important for Gmail:**
- You need an **App Password** (not regular password)
- Go to: https://myaccount.google.com/apppasswords
- Generate App Password for "Mail"
- Use that 16-character password for `MAIL_PASSWORD`

### Session Settings:
```
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

---

## Step 4: Setup Database (DO THIS BEFORE DEPLOYMENT!)

**⚠️ CRITICAL:** Create the database FIRST, then set environment variables, THEN deploy!

1. **Navigate to Infrastructure Section**
   - In Laravel Cloud dashboard, click on your site: **asset-management**
   - Click on your environment: **main** (the blue square icon)
   - Look for **"Infrastructure"** tab or section in the environment dashboard
   - Click on **"Infrastructure"** (this is where databases are managed)

2. **Create Database**
   - In the Infrastructure section, look for **"Add database"** or **"Create Database"** button
   - Click to create a new MySQL database
   - Select **MySQL** (if prompted)
   - Wait for database to be fully created (1-2 minutes)
   - **IMPORTANT:** Click on the database to view details and copy ALL credentials:
     - Database Host (e.g., `mysql-xxxx.laravel.cloud` or similar)
     - Database Port (usually `3306`)
     - Database Name
     - Database Username
     - Database Password

2. **Set Database Environment Variables**
   - Go to **Environment Variables** tab
   - Add the database variables from Step 3 using the credentials you just copied
   - **DO NOT use `127.0.0.1`** - use the actual database host provided by Laravel Cloud

3. **Disable Automatic Migrations (Recommended)**
   - In Laravel Cloud → Your Site → **Deploy Settings** or **Build Settings**
   - Look for "Deploy Commands" or "Post-Deploy Commands"
   - **Remove or comment out:** `php artisan migrate --force`
   - This prevents migrations from running before database is ready
   - We'll run migrations manually after deployment

4. **Save and Deploy**
   - Save all environment variables
   - Trigger deployment
   - Wait for deployment to complete

5. **Run Migrations Manually (After Deployment)**
   - Once deployment is successful
   - Go to Laravel Cloud → Your Site → **Console**
   - Run: `php artisan migrate --force`
   - Verify migrations completed successfully

---

## Step 5: Configure Build Settings

Laravel Cloud auto-detects Laravel, but verify these settings:

### Build Command:
```
composer install --no-dev --optimize-autoloader && npm install && npm run build
```

### Deploy Commands:
**IMPORTANT:** Disable automatic migrations if database isn't ready!

Laravel Cloud may automatically run:
- `php artisan migrate --force` ⚠️ **Disable this if database isn't ready**
- `php artisan storage:link` ✅ Keep this
- `php artisan config:cache` ✅ Keep this
- `php artisan route:cache` ✅ Keep this
- `php artisan view:cache` ✅ Keep this

**Recommended Deploy Commands:**
```
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Then run migrations manually via Console after deployment succeeds.**

---

## Step 6: Setup Scheduled Tasks (CRITICAL!)

**Without this, delay emails won't work!**

1. In Laravel Cloud dashboard → Your Site → **Scheduled Tasks**
2. Click **"Add Scheduled Task"**
3. Configure:
   - **Command:** `php artisan schedule:run`
   - **Frequency:** `* * * * *` (every minute)
   - **Enabled:** ✓

**Alternative:** Laravel Cloud may have a "Scheduler" option - enable it if available.

---

## Step 7: Deploy

1. **Trigger Deployment**
   - Laravel Cloud will automatically deploy when you push to GitHub
   - Or click **"Deploy"** button in the dashboard
   - Or go to **Deployments** → **"Deploy Latest"**

2. **Monitor Deployment**
   - Watch the build logs in real-time
   - Wait for "Deployment successful" message
   - Your app will be live at: `https://your-site-name.laravel.cloud`

---

## Step 8: Update APP_URL

After deployment:

1. Laravel Cloud will provide your site URL (e.g., `https://final-asset-xxxx.laravel.cloud`)
2. Go to **Environment Variables**
3. Update `APP_URL` to match your actual Laravel Cloud URL:
   ```
   APP_URL=https://final-asset-xxxx.laravel.cloud
   ```
4. Save changes (will trigger redeploy)

---

## Step 9: Test Your Application

1. **Visit Your Site**
   - Go to your Laravel Cloud URL
   - You should see the login/register page

2. **Register First Admin User**
   - Click "Register"
   - Fill in the form:
     - Name
     - Username
     - Email
     - Password
   - Click "Register"

3. **Test Features**
   - [ ] Login successfully
   - [ ] Create an asset
   - [ ] Assign asset to employee (should send email)
   - [ ] Create time management task
   - [ ] Wait 5+ minutes, verify delay email works (if task exceeds time)

4. **Check Logs**
   - Go to Laravel Cloud → Your Site → **Logs**
   - Check for any errors

---

## Step 10: Share with Team

1. Share your Laravel Cloud URL with team members
2. Create user accounts for them in the application
3. Provide login credentials

---

## 🔧 Troubleshooting

### App Shows 500 Error
- Check Laravel Cloud logs (Site → Logs)
- Verify `APP_KEY` is set correctly
- Verify database connection variables
- Check if migrations ran successfully

### Database Connection Error / Connection Refused
**This is the most common error!**

- **Check database is created:**
  - Go to Laravel Cloud → Your Site → Click on your environment (e.g., **main**)
  - Click on **"Infrastructure"** tab/section
  - Ensure database exists and is running
  - If not created, click **"Add database"** to create it first

- **Verify database credentials:**
  - In Infrastructure section, click on your database to view details
  - Copy the EXACT credentials shown (Host, Port, Database, Username, Password)
  - Update Environment Variables with these exact values
  - **DO NOT use `127.0.0.1`** - use the actual database host provided

- **Check DB_HOST:**
  - The `DB_HOST` should be something like `mysql-xxxx.laravel.cloud` or similar
  - NOT `127.0.0.1` or `localhost`
  - Copy it exactly from Laravel Cloud database dashboard

- **Verify database is ready:**
  - Database must be fully created before running migrations
  - Wait 1-2 minutes after creating database
  - Check database status in Laravel Cloud dashboard

- **Disable automatic migrations:**
  - If migrations run automatically during deployment, disable them
  - Run migrations manually via Console after deployment succeeds

### Registration Fails
- Check if `username` column exists (run migrations: `php artisan migrate --force`)
- Check Laravel Cloud logs for specific error
- Verify all environment variables are set

### Emails Not Sending
- Verify Gmail App Password is correct (not regular password)
- Check all `MAIL_*` variables are set
- Check Laravel Cloud logs for email errors

### Scheduled Tasks Not Working
- Verify scheduled task is enabled in Laravel Cloud
- Check scheduled task logs
- Ensure command is: `php artisan schedule:run`
- Frequency should be: `* * * * *` (every minute)

### Build Fails
- Check build logs in Laravel Cloud
- Common issues:
  - Missing `APP_KEY`
  - Database not ready
  - npm build failing (check package.json)
  - Composer dependencies issue

---

## 📊 Laravel Cloud Features

**What Laravel Cloud Provides:**
- ✅ Automatic HTTPS/SSL
- ✅ MySQL Database (included)
- ✅ Automatic deployments from GitHub
- ✅ Environment variable management
- ✅ Log viewing
- ✅ Scheduled tasks support
- ✅ Storage management
- ✅ Performance monitoring

---

## ✅ Deployment Checklist

Before going live:
- [ ] Code pushed to GitHub
- [ ] Laravel Cloud account created
- [ ] Site created and connected to GitHub
- [ ] All environment variables set
- [ ] Database created and connected
- [ ] APP_KEY generated and set
- [ ] Migrations run successfully
- [ ] Scheduled tasks configured
- [ ] APP_URL updated to actual Laravel Cloud URL
- [ ] App accessible via Laravel Cloud URL
- [ ] First admin user created
- [ ] Test email sent successfully
- [ ] Scheduled tasks working (delay emails)

---

## 🎉 You're Done!

Your app is now live on Laravel Cloud and ready for your team!

**Your Laravel Cloud URL:** `https://your-site-name.laravel.cloud`

---

## 📝 Quick Reference

**Laravel Cloud Dashboard:** https://cloud.laravel.com

**View Logs:** Laravel Cloud → Your Site → Logs

**Update Code:** Just push to GitHub, Laravel Cloud auto-deploys!

**Update Environment Variables:** Laravel Cloud → Your Site → Environment Variables

**Run Commands:** Laravel Cloud → Your Site → Console

---

## 🔄 Updating Your Application

**To update your app:**
1. Make changes locally
2. Commit and push to GitHub:
   ```bash
   git add .
   git commit -m "Update description"
   git push origin main
   ```
3. Laravel Cloud will automatically detect and deploy
4. Monitor deployment in Laravel Cloud dashboard

---

## 💡 Tips

1. **Always test locally first** before pushing to production
2. **Check logs regularly** to catch issues early
3. **Backup database** before major updates
4. **Monitor scheduled tasks** to ensure they're running
5. **Keep environment variables secure** - never commit `.env` file

---

**Ready to deploy? Start with Step 1!** 🚀
