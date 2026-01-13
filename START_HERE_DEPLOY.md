# 🚀 START HERE - Deploy Your App for Free Testing

## Quick Summary

You want to deploy your Laravel Asset Management app so 5 team members can test it for free, then move to AWS later.

**Best Option: Railway** (Free $5/month credit - perfect for testing!)

---

## ⚡ 5-Minute Quick Start

### 1. Generate App Key (Do this first!)
```bash
php artisan key:generate --show
```
**Save this output** - you'll paste it in Railway!

### 2. Push to GitHub
```bash
git add .
git commit -m "Ready for free deployment"
git push origin main
```

### 3. Deploy on Railway
1. Go to https://railway.app → Sign up (free)
2. Click "New Project" → "Deploy from GitHub"
3. Select your repository
4. Add MySQL database: "New" → "Database" → "MySQL"
5. Add environment variables (see below)
6. Add scheduler service: "New" → "Empty Service" → Command: `php artisan schedule:work`
7. Done! Your app is live!

---

## 📋 Environment Variables for Railway

Copy these into Railway → Your Service → Variables:

```env
APP_NAME=Tanseeq Asset Management
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-APP-NAME.up.railway.app
APP_KEY=base64:PASTE_YOUR_GENERATED_KEY_HERE

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME=Tanseeq Asset Management

APP_TIMEZONE=Asia/Dubai
```

**Important Notes:**
- Replace `APP_KEY` with the key you generated in step 1
- For Gmail, you need an **App Password** (not regular password)
  - Go to: https://myaccount.google.com/apppasswords
  - Generate password and use it for `MAIL_PASSWORD`
- Railway provides the database variables automatically (use the `${{MySQL.XXX}}` format)

---

## ✅ What You Get

- **Free hosting** for testing (5 members)
- **Free MySQL database**
- **Auto-deployment** from GitHub
- **Scheduled tasks** (delay emails work automatically)
- **HTTPS URL** to share with team
- **Cost:** $0 (within free $5/month credit)

---

## 📚 Detailed Guides

- **Full Guide:** See `DEPLOY_FREE.md`
- **Step-by-Step Checklist:** See `DEPLOYMENT_CHECKLIST.md`
- **Quick Reference:** See `QUICK_DEPLOY.md`

---

## 🎯 After Deployment

1. Visit your Railway URL (e.g., `https://your-app.up.railway.app`)
2. Register first admin user
3. Create accounts for your 5 team members
4. Start testing!

---

## 🔄 Later: Move to AWS

When ready for AWS:
1. Export database from Railway
2. Set up AWS RDS (MySQL)
3. Deploy to EC2 or Elastic Beanstalk
4. Update environment variables
5. Point your domain

**For now, Railway is perfect for free testing!** 🚀

---

## ❓ Need Help?

Check the logs in Railway dashboard if something doesn't work. Most issues are:
- Missing APP_KEY
- Wrong database credentials
- Scheduler not running (delay emails won't work)
- Email configuration incorrect

**Ready? Start with step 1 above!** 🎉

