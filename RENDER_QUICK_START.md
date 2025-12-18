# ⚡ Render Quick Start - 10 Minutes

## 🎯 Fastest Way to Deploy

### Step 1: Generate APP_KEY (1 minute)
```bash
php artisan key:generate --show
```
**Copy this!** Example: `base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

### Step 2: Push to GitHub (2 minutes)
```bash
git add .
git commit -m "Ready for Render"
git push origin main
```

### Step 3: Create Database on Render (2 minutes)
1. Go to https://render.com → Sign up (free)
2. Click **"New +"** → **"PostgreSQL"**
3. Name: `final-asset-db`
4. Plan: **Free**
5. Click **"Create Database"**
6. **Copy database credentials** (Host, Port, Database, Username, Password)

### Step 4: Create Web Service (3 minutes)
1. Click **"New +"** → **"Web Service"**
2. Connect GitHub → Select your repo
3. Settings:
   - **Name:** `final-asset`
   - **Runtime:** PHP
   - **Build Command:**
     ```
     composer install --no-dev --optimize-autoloader && npm install && npm run build && php artisan migrate --force && php artisan storage:link
     ```
   - **Start Command:**
     ```
     php artisan serve --host=0.0.0.0 --port=$PORT
     ```
   - **Plan:** Free

4. **Add Environment Variables:**
   ```
   APP_NAME = Tanseeq Asset Management
   APP_ENV = production
   APP_DEBUG = false
   APP_KEY = base64:PASTE_YOUR_KEY_FROM_STEP_1
   APP_TIMEZONE = Asia/Dubai
   
   DB_CONNECTION = pgsql
   DB_HOST = your-db-host
   DB_PORT = 5432
   DB_DATABASE = final_asset
   DB_USERNAME = your-db-username
   DB_PASSWORD = your-db-password
   
   MAIL_MAILER = smtp
   MAIL_HOST = smtp.gmail.com
   MAIL_PORT = 587
   MAIL_USERNAME = your-email@gmail.com
   MAIL_PASSWORD = your-gmail-app-password
   MAIL_ENCRYPTION = tls
   MAIL_FROM_ADDRESS = your-email@gmail.com
   MAIL_FROM_NAME = Tanseeq Asset Management
   ```

5. Click **"Create Web Service"**

### Step 5: Add Scheduler (2 minutes)
1. Click **"New +"** → **"Background Worker"**
2. Same repo, settings:
   - **Name:** `final-asset-scheduler`
   - **Runtime:** PHP
   - **Build Command:** `composer install --no-dev --optimize-autoloader`
   - **Start Command:** `php artisan schedule:work`
   - **Plan:** Free
3. **Copy ALL environment variables** from Step 4
4. Click **"Create Background Worker"**

### Step 6: Update APP_URL
1. Wait for deployment (5-10 minutes)
2. Render gives you URL: `https://final-asset-xxxx.onrender.com`
3. Go to Web Service → Environment
4. Update: `APP_URL = https://final-asset-xxxx.onrender.com`
5. Save (auto-redeploys)

### Step 7: Test!
1. Visit your Render URL
2. Register first admin
3. Test features!

---

## ✅ Done!

**Your app:** `https://your-app-name.onrender.com`

**Cost:** $0 for 90 days, then ~$7/month

**For detailed steps, see:** `RENDER_DEPLOY_STEPS.md`

