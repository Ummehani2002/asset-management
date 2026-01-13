# 🚀 Render Deployment - Step by Step Guide

## Prerequisites Checklist

Before starting, make sure you have:
- [ ] GitHub account (free)
- [ ] Render account (free signup at render.com)
- [ ] Gmail account (for email notifications)
- [ ] Code pushed to GitHub

---

## Step 1: Prepare Your Code

### 1.1 Generate APP_KEY
```bash
php artisan key:generate --show
```
**Copy the output** - you'll need it in Step 4!

Example output: `base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

### 1.2 Push to GitHub

If not already done:
```bash
# Initialize git (if needed)
git init

# Add all files
git add .

# Commit
git commit -m "Ready for Render deployment"

# Add remote (replace with your GitHub repo URL)
git remote add origin https://github.com/YOUR_USERNAME/final_asset.git

# Push to GitHub
git branch -M main
git push -u origin main
```

---

## Step 2: Sign Up on Render

1. Go to **https://render.com**
2. Click **"Get Started for Free"**
3. Sign up with **GitHub** (easiest option)
4. Authorize Render to access your GitHub repositories

---

## Step 3: Create PostgreSQL Database

1. In Render dashboard, click **"New +"**
2. Select **"PostgreSQL"**
3. Configure:
   - **Name:** `final-asset-db`
   - **Database:** `final_asset`
   - **User:** `finalassetuser` (or leave default)
   - **Region:** Choose closest to you
   - **Plan:** Select **"Free"** (for testing)
4. Click **"Create Database"**
5. **Wait for database to be created** (takes 1-2 minutes)
6. **IMPORTANT:** Note down these details from the database dashboard:
   - **Internal Database URL** (you'll need this)
   - **Host, Port, Database, Username, Password**

---

## Step 4: Create Web Service

1. In Render dashboard, click **"New +"**
2. Select **"Web Service"**
3. Connect your GitHub account (if not already connected)
4. Select your repository: `final_asset` (or your repo name)
5. Click **"Connect"**

### Configure Web Service:

**Basic Settings:**
- **Name:** `final-asset` (or any name you like)
- **Region:** Same as database (for better performance)
- **Branch:** `main` (or `master`)
- **Root Directory:** Leave empty (or `./` if needed)
- **Runtime:** `PHP`
- **Build Command:** 
  ```
  composer install --no-dev --optimize-autoloader && npm install && npm run build && php artisan migrate --force && php artisan storage:link
  ```
- **Start Command:**
  ```
  php artisan serve --host=0.0.0.0 --port=$PORT
  ```
- **Plan:** Select **"Free"** (for testing)

### Environment Variables:

Click **"Advanced"** → **"Add Environment Variable"**, then add these one by one:

**Application Settings:**
```
APP_NAME = Tanseeq Asset Management
APP_ENV = production
APP_DEBUG = false
APP_KEY = base64:PASTE_YOUR_KEY_FROM_STEP_1
APP_URL = https://your-app-name.onrender.com
APP_TIMEZONE = Asia/Dubai
```

**Database Settings (from Step 3):**
```
DB_CONNECTION = pgsql
DB_HOST = your-db-host-from-render
DB_PORT = 5432
DB_DATABASE = final_asset
DB_USERNAME = your-db-username
DB_PASSWORD = your-db-password
```

**Email Settings:**
```
MAIL_MAILER = smtp
MAIL_HOST = smtp.gmail.com
MAIL_PORT = 587
MAIL_USERNAME = your-email@gmail.com
MAIL_PASSWORD = your-gmail-app-password
MAIL_ENCRYPTION = tls
MAIL_FROM_ADDRESS = your-email@gmail.com
MAIL_FROM_NAME = Tanseeq Asset Management
```

**Important Notes:**
- Replace `APP_KEY` with the key you generated in Step 1.1
- For `APP_URL`, Render will provide the URL after deployment (you can update it later)
- For Gmail, you need an **App Password** (not regular password):
  - Go to: https://myaccount.google.com/apppasswords
  - Generate App Password for "Mail"
  - Use that 16-character password for `MAIL_PASSWORD`

6. Click **"Create Web Service"**

---

## Step 5: Wait for Deployment

1. Render will start building your application
2. This takes **5-10 minutes** the first time
3. Watch the build logs:
   - Should see: "Installing dependencies..."
   - Should see: "Running migrations..."
   - Should see: "Build successful"
4. Your app will be live at: `https://your-app-name.onrender.com`

---

## Step 6: Setup Scheduled Tasks (CRITICAL!)

**Without this, delay emails won't work!**

1. In Render dashboard, click **"New +"**
2. Select **"Background Worker"**
3. Connect same GitHub repository
4. Configure:
   - **Name:** `final-asset-scheduler`
   - **Region:** Same as web service
   - **Branch:** `main`
   - **Root Directory:** Leave empty
   - **Runtime:** `PHP`
   - **Build Command:** 
     ```
     composer install --no-dev --optimize-autoloader
     ```
   - **Start Command:**
     ```
     php artisan schedule:work
     ```
   - **Plan:** Select **"Free"**

5. **Add Same Environment Variables:**
   - Copy all environment variables from Step 4
   - Add them to this Background Worker too
   - **Especially important:** Database and APP_KEY

6. Click **"Create Background Worker"**

---

## Step 7: Update APP_URL

1. After deployment, Render will give you a URL like: `https://final-asset-xxxx.onrender.com`
2. Go to your Web Service → **Environment** tab
3. Update `APP_URL` to match your actual Render URL:
   ```
   APP_URL = https://final-asset-xxxx.onrender.com
   ```
4. Click **"Save Changes"**
5. Render will automatically redeploy

---

## Step 8: Test Your Application

1. Visit your Render URL: `https://your-app-name.onrender.com`
2. **Register first admin user:**
   - Click "Register"
   - Create your admin account
   - Login

3. **Test Features:**
   - [ ] Create an asset
   - [ ] Assign asset to employee (should send email)
   - [ ] Create time management task
   - [ ] Wait 5+ minutes, verify delay email works (if task exceeds time)

4. **Check Logs:**
   - Go to Render → Your Web Service → **Logs** tab
   - Check for any errors

---

## Step 9: Share with Team

1. Share your Render URL with 5 team members
2. Create user accounts for them in the application
3. Provide login credentials

---

## 🔧 Troubleshooting

### App Shows 500 Error
- Check Render logs (Web Service → Logs)
- Verify `APP_KEY` is set correctly
- Verify database connection variables

### Database Connection Error
- Double-check database credentials
- Ensure database is running (check in Render dashboard)
- Verify `DB_CONNECTION=pgsql` (PostgreSQL, not MySQL)

### Emails Not Sending
- Verify Gmail App Password is correct (not regular password)
- Check all `MAIL_*` variables are set
- Check Render logs for email errors

### Scheduled Tasks Not Working
- Verify Background Worker is running
- Check Background Worker logs
- Ensure all environment variables are set in Background Worker too

### Build Fails
- Check build logs in Render
- Common issues:
  - Missing `APP_KEY`
  - Database not ready
  - npm build failing (check package.json)

---

## 📊 Cost

**Render Free Tier:**
- Web Service: Free (but sleeps after 15 min inactivity)
- PostgreSQL: Free (limited to 90 days, then $7/month)
- Background Worker: Free

**For Testing (5 members):**
- **Cost: $0** for first 90 days
- After 90 days: ~$7/month for database (still very cheap!)

---

## ✅ Success Checklist

- [ ] Code pushed to GitHub
- [ ] Render account created
- [ ] PostgreSQL database created
- [ ] Web Service deployed and running
- [ ] Background Worker (scheduler) running
- [ ] All environment variables set
- [ ] APP_URL updated to actual Render URL
- [ ] App accessible via Render URL
- [ ] First admin user created
- [ ] Test email sent successfully
- [ ] Scheduled tasks working (delay emails)

---

## 🎉 You're Done!

Your app is now live on Render and ready for your 5 team members to test!

**Your Render URL:** `https://your-app-name.onrender.com`

---

## 📝 Quick Reference

**Render Dashboard:** https://dashboard.render.com

**View Logs:** Render → Your Service → Logs tab

**Update Code:** Just push to GitHub, Render auto-deploys!

**Update Environment Variables:** Render → Your Service → Environment tab

---

## 🔄 Later: Move to AWS

When ready:
1. Export database from Render PostgreSQL
2. Import to AWS RDS (MySQL or PostgreSQL)
3. Deploy to AWS EC2 or Elastic Beanstalk
4. Update environment variables
5. Point your domain

**For now, Render is perfect for free testing!** 🚀

