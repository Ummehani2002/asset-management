# Reset User Password in Production

When a user cannot log in and the **Forgot Password** page returns a **500 Server Error** (e.g. at `https://asset-management.laravel.cloud/password/email`), you can reset their password manually in production using one of the methods below.

---

## Option 1: Artisan command (recommended on Laravel Cloud)

Use the Laravel Cloud **Console** (or SSH into the server) and run:

```bash
# List users to find the correct email or username
php artisan user:list

# Reset password by email or username
php artisan user:reset-password "user@example.com" "newSecurePassword123!"
# or by username
php artisan user:reset-password "badruddin" "badr@10159!"
```

**Example for Badruddin M:**  
If his email is `badruddin@company.com` or his username is `badruddin`:

```bash
php artisan user:reset-password "badruddin@company.com" "badr@10159!"
# or
php artisan user:reset-password "badruddin" "badr@10159!"
```

Use the actual email or username from your system (check with `php artisan user:list`).

---

## Option 2: Admin UI (if you can log in as admin)

1. Log in at `https://asset-management.laravel.cloud` as an **admin** user.
2. Open **Users** in the sidebar.
3. Find the user (e.g. Badruddin M) and click **Edit**.
4. Enter the new password in **New Password** and **Confirm Password**.
5. Click **Update User**.

The user can then log in with the new password.

---

## Option 3: Tinker

In Laravel Cloud Console (or SSH):

```bash
php artisan tinker
```

Then:

```php
$user = App\Models\User::where('email', 'user@example.com')->orWhere('username', 'badruddin')->first();
$user->password = Illuminate\Support\Facades\Hash::make('badr@10159!');
$user->save();
exit
```

---

## Why does /password/email return 500?

The **Forgot Password** flow sends an email with a reset link. A 500 error there is usually due to:

| Cause | What to do |
|-------|------------|
| **Mail not configured** | In Laravel Cloud **Environment → Variables**, set `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`. See [DEPLOY_TO_NEW_LARAVEL_CLOUD.md](DEPLOY_TO_NEW_LARAVEL_CLOUD.md). |
| **Wrong APP_URL** | Set `APP_URL` to your production URL (e.g. `https://asset-management.laravel.cloud`) so reset links are correct. |
| **See actual error** | Temporarily set `APP_DEBUG=true`, reproduce the request, check **Logs** in Laravel Cloud (or `storage/logs/laravel.log`), fix the issue, then set `APP_DEBUG=false` again. |

Until mail is fixed, use **Option 1** or **Option 2** above to reset passwords.

---

## Quick reference

```bash
# List users
php artisan user:list

# Reset password (production / Laravel Cloud Console)
php artisan user:reset-password "email_or_username" "NewPassword123!"
```
