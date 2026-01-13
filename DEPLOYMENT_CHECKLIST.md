# ✅ Deployment Checklist - Free Hosting for 5 Members

## Pre-Deployment (Do This First!)

### 1. Generate APP_KEY
```bash
php artisan key:generate --show
```
**Copy the output** - you'll need it for Railway/Render

### 2. Prepare Gmail for Email (if using Gmail)
- [ ] Enable 2-Step Verification in Google Account
- [ ] Generate App Password: https://myaccount.google.com/apppasswords
- [ ] Copy the 16-character password

### 3. Push to GitHub
```bash
git add .
git commit -m "Ready for deployment"
git push origin main
```

---

## 🚀 Railway Deployment (Recommended - 15 minutes)

### Step 1: Sign Up
- [ ] Go to https://railway.app
- [ ] Sign up with GitHub (free)

### Step 2: Create Project
- [ ] Click "New Project"
- [ ] Select "Deploy from GitHub repo"
- [ ] Choose your repository
- [ ] Railway auto-detects Laravel ✓

### Step 3: Add Database
- [ ] Click "New" → "Database" → "MySQL"
- [ ] Railway creates database automatically ✓

### Step 4: Set Environment Variables
Go to your service → Variables tab, add:

**Application:**
- [ ] `APP_NAME` = `Tanseeq Asset Management`
- [ ] `APP_ENV` = `production`
- [ ] `APP_DEBUG` = `false`
- [ ] `APP_URL` = `https://YOUR-APP-NAME.up.railway.app` (Railway provides this)
- [ ] `APP_KEY` = `base64:YOUR_GENERATED_KEY` (from step 1)

**Database (Use Railway's auto-variables):**
- [ ] `DB_CONNECTION` = `mysql`
- [ ] `DB_HOST` = `${{MySQL.MYSQLHOST}}`
- [ ] `DB_PORT` = `${{MySQL.MYSQLPORT}}`
- [ ] `DB_DATABASE` = `${{MySQL.MYSQLDATABASE}}`
- [ ] `DB_USERNAME` = `${{MySQL.MYSQLUSER}}`
- [ ] `DB_PASSWORD` = `${{MySQL.MYSQLPASSWORD}}`

**Email:**
- [ ] `MAIL_MAILER` = `smtp`
- [ ] `MAIL_HOST` = `smtp.gmail.com`
- [ ] `MAIL_PORT` = `587`
- [ ] `MAIL_USERNAME` = `your-email@gmail.com`
- [ ] `MAIL_PASSWORD` = `your-gmail-app-password` (from step 2)
- [ ] `MAIL_ENCRYPTION` = `tls`
- [ ] `MAIL_FROM_ADDRESS` = `your-email@gmail.com`
- [ ] `MAIL_FROM_NAME` = `Tanseeq Asset Management`

**Timezone:**
- [ ] `APP_TIMEZONE` = `Asia/Dubai`

### Step 5: Setup Scheduler (CRITICAL!)
- [ ] Click "New" → "Empty Service"
- [ ] Name it "Scheduler"
- [ ] Set command: `php artisan schedule:work`
- [ ] **Without this, delay emails won't work!**

### Step 6: Deploy
- [ ] Railway automatically builds and deploys
- [ ] Wait for "Deployed" status
- [ ] Copy your app URL (e.g., `https://final-asset-production.up.railway.app`)

### Step 7: Test
- [ ] Visit your app URL
- [ ] Register first admin user
- [ ] Test asset assignment (should send email)
- [ ] Test time management (delay emails should work)

### Step 8: Share with Team
- [ ] Share the Railway URL with 5 members
- [ ] Create user accounts for them
- [ ] Provide login credentials

---

## 📊 Cost Tracking

**Railway Free Tier:**
- $5 credit/month
- Estimated usage: ~$3-5/month for 5 users
- **You're covered!** ✓

**When to upgrade:**
- If you exceed $5/month
- If you need better performance
- When moving to AWS (later)

---

## 🔧 Troubleshooting

**App not loading?**
- Check Railway logs
- Verify APP_KEY is set
- Ensure database is connected

**Emails not sending?**
- Verify Gmail app password is correct
- Check MAIL_* variables
- Test email in Laravel logs

**Scheduled tasks not working?**
- Ensure "Scheduler" service is running
- Check command: `php artisan schedule:work`
- Verify it's not stopped

**Database errors?**
- Verify DB variables use `${{MySQL.XXX}}` format
- Check database service is running
- Run migrations manually if needed

---

## 📝 Quick Reference

**Railway Dashboard:** https://railway.app/dashboard

**Your App URL:** Check Railway → Your Service → Settings → Domains

**View Logs:** Railway → Your Service → Deployments → Click deployment → View logs

**Update Code:** Just push to GitHub, Railway auto-deploys!

---

## ✅ You're Done!

Your app is now live and ready for your 5 team members to test! 🎉

