# 🚀 Free Deployment Guide - Tanseeq Asset Management

## Best Free Hosting Options for Testing (5 Members)

### Option 1: Railway (Recommended - $5 Free Credit/Month)
**Best for:** Easy setup, auto-deployment, MySQL included
**Free Tier:** $5 credit/month (enough for testing)

### Option 2: Render (Free Tier Available)
**Best for:** Simple deployment, free PostgreSQL
**Free Tier:** Free tier available (slower, but works for testing)

### Option 3: Fly.io (Free Tier)
**Best for:** Global deployment, good performance
**Free Tier:** 3 shared-cpu-1x VMs free

---

## 🎯 Quick Deployment on Railway (Recommended)

### Prerequisites:
1. GitHub account (free)
2. Railway account (free signup at railway.app)

### Step 1: Push Code to GitHub

```bash
# If not already a git repo
git init
git add .
git commit -m "Initial commit - ready for deployment"

# Create a new repository on GitHub, then:
git remote add origin https://github.com/YOUR_USERNAME/final_asset.git
git branch -M main
git push -u origin main
```

### Step 2: Deploy on Railway

1. **Sign up/Login**
   - Go to [railway.app](https://railway.app)
   - Sign up with GitHub (free)

2. **Create New Project**
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Choose your `final_asset` repository
   - Railway will auto-detect Laravel

3. **Add MySQL Database**
   - In your Railway project, click "New"
   - Select "Database" → "MySQL"
   - Railway will automatically create and provide credentials

4. **Configure Environment Variables**
   - Go to your service → Variables tab
   - Add these variables:

```env
# Application
APP_NAME="Tanseeq Asset Management"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-APP-NAME.up.railway.app
APP_KEY=base64:YOUR_GENERATED_KEY_HERE

# Database (Railway auto-provides these - use the ${{MySQL.XXX}} format)
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

# Email Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Tanseeq Asset Management"

# Timezone
APP_TIMEZONE=Asia/Dubai
```

5. **Generate APP_KEY**
   ```bash
   # Run locally or in Railway console:
   php artisan key:generate --show
   # Copy the output and paste as APP_KEY value
   ```

6. **Setup Scheduled Tasks (IMPORTANT!)**
   - In Railway project, click "New" → "Empty Service"
   - Name it "Scheduler"
   - Set the command: `php artisan schedule:work`
   - This runs the delayed task checker every 5 minutes
   - **Without this, delay emails won't work!**

7. **Deploy**
   - Railway will automatically:
     - Install dependencies
     - Run migrations
     - Start your app
   - Your app will be live at: `https://YOUR-APP-NAME.up.railway.app`

### Step 3: Access Your Application

1. Railway will provide a URL like: `https://your-app-name.up.railway.app`
2. Share this URL with your 5 team members
3. Create user accounts for them in the application

---

## 🎯 Alternative: Render (Free Tier)

### Step 1: Push to GitHub (same as above)

### Step 2: Deploy on Render

1. Go to [render.com](https://render.com) and sign up
2. Click "New" → "Web Service"
3. Connect your GitHub repository
4. Configure:
   - **Name:** final-asset
   - **Environment:** PHP
   - **Build Command:** `composer install --no-dev --optimize-autoloader && php artisan migrate --force && php artisan storage:link`
   - **Start Command:** `php artisan serve --host=0.0.0.0 --port=$PORT`

5. **Add PostgreSQL Database**
   - Click "New" → "PostgreSQL"
   - Note the connection details

6. **Environment Variables** (same as Railway, but use PostgreSQL):
```env
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password
```

7. **Setup Cron Job**
   - In Render, go to "Cron Jobs"
   - Add: `*/5 * * * * cd /opt/render/project/src && php artisan schedule:run`

---

## 📋 Pre-Deployment Checklist

Before deploying, ensure:

- [ ] All code is committed to GitHub
- [ ] `.env` file is NOT in git (it's in .gitignore)
- [ ] `APP_KEY` is generated
- [ ] Database migrations are ready
- [ ] Email configuration is set up
- [ ] Storage link will be created (`php artisan storage:link`)

---

## 🔧 Post-Deployment Steps

1. **Create Admin User**
   - Access your deployed app
   - Register first user (or use tinker to create admin)

2. **Test Email Functionality**
   - Try assigning an asset
   - Verify email is sent

3. **Test Scheduled Tasks**
   - Create a time management task
   - Wait 5 minutes
   - Verify delay email is sent if task exceeds time

4. **Share with Team**
   - Share the Railway/Render URL
   - Create accounts for all 5 members
   - Provide login credentials

---

## 💰 Cost Estimate

### Railway:
- **Free Tier:** $5 credit/month
- **Estimated Cost:** ~$3-5/month for testing (well within free tier)
- **After Free Credit:** ~$5-10/month

### Render:
- **Free Tier:** Available (slower, but free)
- **Upgrade:** $7/month for better performance

---

## 🚀 Migration to AWS (Later)

When ready to move to AWS:

1. **AWS Services Needed:**
   - EC2 (or Elastic Beanstalk)
   - RDS (MySQL)
   - S3 (for file storage)
   - SES (for emails)

2. **Migration Steps:**
   - Export database from Railway/Render
   - Import to AWS RDS
   - Deploy code to EC2/Elastic Beanstalk
   - Update environment variables
   - Point domain to AWS

---

## 📞 Support

If you encounter issues:
1. Check Railway/Render logs
2. Verify all environment variables are set
3. Ensure database is connected
4. Check scheduled tasks are running

---

## ✅ Quick Commands Reference

```bash
# Generate APP_KEY
php artisan key:generate --show

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

**Ready to deploy? Start with Railway - it's the easiest option!** 🚀

