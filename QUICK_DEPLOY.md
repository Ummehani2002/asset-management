# 🚀 Quick Deployment Guide

## For Railway (Easiest - Recommended)

### Step 1: Push to GitHub
```bash
git add .
git commit -m "Ready for deployment"
git push origin main
```

### Step 2: Deploy on Railway
1. Go to [railway.app](https://railway.app) and login
2. Click **"New Project"** → **"Deploy from GitHub repo"**
3. Select your repository
4. Railway will auto-detect Laravel

### Step 3: Add MySQL Database
1. In Railway project, click **"New"** → **"Database"** → **"MySQL"**
2. Railway provides DB credentials automatically

### Step 4: Configure Environment Variables
In Railway → Your Service → Variables, add:

**Required Variables:**
```
APP_NAME=Tanseeq Asset Management
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.up.railway.app
APP_KEY=base64:YOUR_KEY_HERE  # Generate with: php artisan key:generate --show
```

**Database (Auto-filled by Railway MySQL):**
```
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
```

**Email Configuration:**
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME=Tanseeq Asset Management
```

### Step 5: Setup Scheduled Tasks (IMPORTANT!)
1. In Railway, add a **new service**
2. Select **"Empty Service"**
3. Set command: `php artisan schedule:work`
4. This runs delayed task checker every 5 minutes automatically
5. **Without this, delay emails won't be sent!**

### Step 6: Deploy!
Railway will automatically:
- Install dependencies
- Run migrations
- Start your app

Your app will be live at: `https://your-app-name.up.railway.app`

---

## Important Notes

### Generate APP_KEY
Before deploying, generate your app key:
```bash
php artisan key:generate --show
```
Copy the output and paste it as `APP_KEY` in Railway variables.

### Gmail App Password
For Gmail, you need an **App Password** (not regular password):
1. Go to Google Account → Security
2. Enable 2-Step Verification
3. Generate App Password
4. Use that as `MAIL_PASSWORD`

### First Login
After deployment:
1. Visit your app URL
2. Register the first admin user
3. Start using the system!

---

## Need Help?

Check `DEPLOYMENT.md` for detailed instructions and troubleshooting.

