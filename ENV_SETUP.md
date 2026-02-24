# Environment (.env) Setup Guide

Use this guide to set your `.env` file. Copy `.env.example` to `.env` if you don't have one:

```bash
cp .env.example .env
php artisan key:generate
```

---

## 1. App basics

```env
APP_NAME="Asset Management"
APP_ENV=local
APP_KEY=base64:xxxx   # run: php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost
```

- **Production:** set `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL=https://your-domain.com`

---

## 2. Database

**MySQL (typical for this project):**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=final_asset
DB_USERNAME=root
DB_PASSWORD=your_password
```

**SQLite (from .env.example):**

```env
DB_CONNECTION=sqlite
# Comment out or remove DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# Then run: touch database/database.sqlite && php artisan migrate
```

---

## 3. Mail (so approval emails are sent)

### Option A: Gmail (e.g. haniumme698@gmail.com)

1. In Gmail: **Settings → See all settings → Accounts → 2-Step Verification** (turn it on).
2. Then **Security → App passwords** and create an app password for “Mail”.
3. In `.env` set:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=haniumme698@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=haniumme698@gmail.com
MAIL_FROM_NAME="Asset Management"
```

Use the **16-character app password** (with or without spaces) as `MAIL_PASSWORD`. Do **not** use your normal Gmail password.

### Option B: Log only (no real email)

Emails are written to `storage/logs/laravel.log` instead of being sent:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=haniumme698@gmail.com
MAIL_FROM_NAME="Asset Management"
```

### Option C: Microsoft 365 / Outlook

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=youruser@yourcompany.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=youruser@yourcompany.com
MAIL_FROM_NAME="Asset Management"
```

### Option D: Microsoft Graph (Azure)

Use the existing `MICROSOFT_GRAPH_*` variables in `.env.example` and set `MAIL_MAILER=microsoft-graph`.

---

## 4. After changing .env

- Run: `php artisan config:clear`
- If you use queue: `php artisan queue:restart` (when using queue for mail)

---

## 5. Quick checklist

| Item              | Variable / Action                          |
|-------------------|--------------------------------------------|
| App key           | `php artisan key:generate`                 |
| Database          | Set `DB_*` and run `php artisan migrate`   |
| Send real email   | Set `MAIL_MAILER=smtp` and Gmail/outgoing  |
| From address      | `MAIL_FROM_ADDRESS=haniumme698@gmail.com`  |
| Production URL    | `APP_URL=https://your-domain.com`          |
