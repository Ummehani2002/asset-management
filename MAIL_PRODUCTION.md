# Mail Not Working in Production – Checklist

If you're not getting emails in production, follow this checklist.

---

## 1. Set environment variables in production

In **Laravel Cloud** (or your host): **Environment → Variables**. Add or confirm:

### Required for sending real email (Gmail example)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=haniumme698@gmail.com
MAIL_PASSWORD=your_16_char_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=haniumme698@gmail.com
MAIL_FROM_NAME="Asset Management"
APP_URL=https://asset-management.laravel.cloud
```

- **MAIL_MAILER** must be `smtp` (not `log`). If it's `log`, emails only go to the log file.
- **MAIL_PASSWORD** for Gmail must be an [App Password](https://myaccount.google.com/apppasswords), not your normal password. Use quotes if it has spaces: `"xxxx xxxx xxxx xxxx"`.
- **APP_URL** must be your real production URL (e.g. `https://asset-management.laravel.cloud`) so links in emails are correct.

### If using a different provider

| Provider | MAIL_HOST | MAIL_PORT | MAIL_ENCRYPTION |
|----------|-----------|-----------|-----------------|
| Gmail | smtp.gmail.com | 587 | tls |
| Outlook/Office 365 | smtp.office365.com | 587 | tls |
| SendGrid | smtp.sendgrid.net | 587 | tls |
| Mailgun | smtp.mailgun.org | 587 | tls |

Use the provider’s docs for `MAIL_USERNAME` and `MAIL_PASSWORD`.

---

## How approval emails are sent in production

Approval emails use **the same mail config** as `php artisan mail:test`. There is no queue: when someone clicks **Request for Approval**, the email is sent immediately.

**To get approval emails in production:**

1. **Mail config** – Same as for the test. Set `MAIL_MAILER=smtp` and all `MAIL_*` vars in production (see above). If `mail:test --to=you@email.com` works, approval emails will use the same config.
2. **APP_URL** – Must be your production URL (e.g. `https://asset-management.laravel.cloud`) so the **Approve** and **Reject** links in the email point to your site.
3. **Who receives the email** – The User whose `employee_id` is the asset manager for that asset (or the Employee’s email, or fallback to `MAIL_FROM_ADDRESS`). Ensure the asset manager has an email (Users → edit user → set email, or set Employee email in Employee Master).

**Flow:** User opens System Maintenance → Send for Maintenance → selects category and asset → clicks **Request for Approval** → one email is sent right away to the asset manager. No queue worker needed.

---

## 2. Clear config cache after changing env

After changing any `MAIL_*` or `APP_URL` in production:

```bash
php artisan config:clear
php artisan config:cache
```

(Laravel Cloud: run these in **Console** or in **Deploy Commands**.)

---

## 3. Test from production

In production **Console** run:

```bash
php artisan mail:test --to=haniumme698@gmail.com
```

- If it says **Test email sent** → check inbox (and spam). If nothing arrives, the host may be blocking outbound SMTP.
- If it says **Failed to send: …** → use that error to fix (wrong credentials, port blocked, etc.).
- If **Mailer is "log"** → set `MAIL_MAILER=smtp` (and other vars above) in production env, then run `php artisan config:clear` and try again.

---

## 4. Typical “no mail in production” causes

| Cause | What to do |
|-------|------------|
| **MAIL_MAILER=log** | Set `MAIL_MAILER=smtp` and configure SMTP in env. |
| **MAIL_* not set** | Add all `MAIL_*` variables in production Environment. |
| **Config cached with old values** | Run `php artisan config:clear` then `php artisan config:cache`. |
| **Gmail: normal password** | Use an App Password for `MAIL_PASSWORD`, not your Gmail password. |
| **Spam folder** | Check spam/junk; add MAIL_FROM_ADDRESS to contacts. |
| **Port 587 blocked** | Try port 465 with `MAIL_ENCRYPTION=ssl`, or use a provider that allows 25. |
| **Wrong APP_URL** | Set `APP_URL` to exact production URL (https://…) so links in emails work. |

---

## 5. Check production logs

If the app says “request sent” but no email arrives:

1. In Laravel Cloud (or your host) open **Logs** or `storage/logs/laravel.log`.
2. Look for:
   - `Maintenance approval email sent to …` → mail was attempted.
   - `Maintenance approval request email failed` → see the error message and fix the cause.

---

## 6. Quick copy-paste for Gmail (production)

1. Create a Gmail App Password: [Google App Passwords](https://myaccount.google.com/apppasswords).
2. In production Environment add:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=haniumme698@gmail.com
MAIL_PASSWORD="your 16 char app password here"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=haniumme698@gmail.com
MAIL_FROM_NAME="Asset Management"
```

3. Set `APP_URL` to your production URL.
4. Run: `php artisan config:clear`
5. Test: `php artisan mail:test --to=haniumme698@gmail.com`

---

After this, maintenance approval emails should be sent from production. If they still don’t arrive, use the error from `mail:test` or from the logs to fix the last remaining issue (credentials, port, or firewall).
