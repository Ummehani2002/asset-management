# Deployment Guide - Tanseeq Asset Management System

## 🚀 Quick Deployment Options

### Option 1: Railway (Recommended - Already Set Up)
Railway is a modern platform that makes deployment easy.

#### Steps:
1. **Push to GitHub/GitLab**
   ```bash
   git init
   git add .
   git commit -m "Initial deployment"
   git remote add origin <your-repo-url>
   git push -u origin main
   ```

2. **Deploy on Railway**
   - Go to [railway.app](https://railway.app)
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Connect your repository
   - Railway will auto-detect Laravel

3. **Configure Environment Variables**
   In Railway dashboard, add these variables:
   ```
   APP_NAME="Tanseeq Asset Management"
   APP_ENV=production
   APP_KEY=base64:YOUR_APP_KEY_HERE
   APP_DEBUG=false
   APP_URL=https://your-app-name.up.railway.app
   
   DB_CONNECTION=mysql
   DB_HOST=your-db-host
   DB_PORT=3306
   DB_DATABASE=your-db-name
   DB_USERNAME=your-db-user
   DB_PASSWORD=your-db-password
   
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=your-email@gmail.com
   MAIL_FROM_NAME="Tanseeq Asset Management"
   
   TIMEZONE=Asia/Dubai
   ```

4. **Add MySQL Database**
   - In Railway, click "New" → "Database" → "MySQL"
   - Railway will auto-provide DB credentials

5. **Set Build Command**
   Railway will auto-detect, but ensure:
   ```
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan storage:link
   ```

6. **Set Start Command**
   ```
   php artisan serve --host=0.0.0.0 --port=$PORT
   ```

7. **Setup Cron Job (for scheduled tasks)**
   Add a new service in Railway:
   - Command: `php artisan schedule:work`
   - This runs the delayed task checker every 30 minutes

---

### Option 2: Traditional VPS (DigitalOcean, AWS, etc.)

#### Requirements:
- PHP 8.2+
- MySQL/MariaDB
- Composer
- Node.js & NPM
- Web server (Nginx/Apache)

#### Steps:

1. **Server Setup**
   ```bash
   # Update system
   sudo apt update && sudo apt upgrade -y
   
   # Install PHP 8.2
   sudo apt install php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd -y
   
   # Install Composer
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   
   # Install Node.js
   curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
   sudo apt install -y nodejs
   
   # Install MySQL
   sudo apt install mysql-server -y
   ```

2. **Clone and Setup**
   ```bash
   cd /var/www
   git clone <your-repo-url> final_asset
   cd final_asset
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure .env**
   ```env
   APP_NAME="Tanseeq Asset Management"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=final_asset
   DB_USERNAME=your_user
   DB_PASSWORD=your_password
   ```

4. **Database Setup**
   ```bash
   mysql -u root -p
   CREATE DATABASE final_asset;
   CREATE USER 'your_user'@'localhost' IDENTIFIED BY 'your_password';
   GRANT ALL PRIVILEGES ON final_asset.* TO 'your_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   
   php artisan migrate --force
   php artisan storage:link
   ```

5. **Build Assets**
   ```bash
   npm install
   npm run build
   ```

6. **Setup Nginx**
   Create `/etc/nginx/sites-available/final_asset`:
   ```nginx
   server {
       listen 80;
       server_name yourdomain.com;
       root /var/www/final_asset/public;
       
       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";
       
       index index.php;
       
       charset utf-8;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location = /favicon.ico { access_log off; log_not_found off; }
       location = /robots.txt  { access_log off; log_not_found off; }
       
       error_page 404 /index.php;
       
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }
       
       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```
   
   Enable site:
   ```bash
   sudo ln -s /etc/nginx/sites-available/final_asset /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   ```

7. **Setup SSL (Let's Encrypt)**
   ```bash
   sudo apt install certbot python3-certbot-nginx -y
   sudo certbot --nginx -d yourdomain.com
   ```

8. **Setup Cron Job**
   ```bash
   sudo crontab -e
   ```
   Add:
   ```
   * * * * * cd /var/www/final_asset && php artisan schedule:run >> /dev/null 2>&1
   ```

9. **Set Permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/final_asset
   sudo chmod -R 755 /var/www/final_asset
   sudo chmod -R 775 /var/www/final_asset/storage
   sudo chmod -R 775 /var/www/final_asset/bootstrap/cache
   ```

---

## 📋 Pre-Deployment Checklist

- [ ] Generate APP_KEY: `php artisan key:generate`
- [ ] Set APP_ENV=production
- [ ] Set APP_DEBUG=false
- [ ] Configure database credentials
- [ ] Configure mail settings
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Create storage link: `php artisan storage:link`
- [ ] Build assets: `npm run build`
- [ ] Setup cron job for scheduled tasks
- [ ] Test email sending
- [ ] Test file uploads
- [ ] Backup database

---

## 🔧 Important Configuration

### Mail Configuration
For Gmail:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password  # Use App Password, not regular password
MAIL_ENCRYPTION=tls
```

### Storage Configuration
Ensure `storage/app/public` is writable and linked:
```bash
php artisan storage:link
chmod -R 775 storage
```

### Scheduled Tasks
The system needs to run `php artisan schedule:run` every minute (or use `schedule:work`) for:
- Delayed task email alerts (checks every 5 minutes automatically)
- When a task exceeds standard hours, email is sent within 5 minutes

---

## 🐛 Troubleshooting

### Issue: 500 Error
- Check `.env` file exists and is configured
- Check file permissions: `chmod -R 775 storage bootstrap/cache`
- Check logs: `tail -f storage/logs/laravel.log`

### Issue: Database Connection Error
- Verify database credentials in `.env`
- Ensure database exists
- Check database user has proper permissions

### Issue: Emails Not Sending
- Verify mail configuration in `.env`
- For Gmail, use App Password (not regular password)
- Check mail logs: `storage/logs/laravel.log`

### Issue: Images Not Showing
- Run: `php artisan storage:link`
- Check `public/storage` symlink exists
- Verify file permissions

---

## 📞 Support

For deployment issues, check:
1. Laravel logs: `storage/logs/laravel.log`
2. Server logs: `/var/log/nginx/error.log` (for Nginx)
3. Railway logs: Dashboard → Your Service → Logs

---

## 🔐 Security Notes

- Never commit `.env` file
- Use strong database passwords
- Enable HTTPS/SSL
- Keep Laravel and dependencies updated
- Regularly backup database
- Use environment variables for sensitive data

