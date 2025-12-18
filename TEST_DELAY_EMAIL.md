# Testing Delay Email Alerts

## Issue: Not Receiving Delay Emails

If you set 1 hour for a task and it exceeded 1 hour but didn't get an email, here's how to fix it:

## Problem
The scheduled command `tasks:check-delayed` needs to run automatically. It's configured to run every 5 minutes, but the scheduler must be active.

## Solutions

### Option 1: For Local Development/Testing

Run the scheduler manually:
```bash
php artisan schedule:work
```
This will keep running and check for delayed tasks every 5 minutes automatically.

**OR** on Windows, double-click: `START_SCHEDULER.bat`

**OR** Test the command directly:
```bash
php artisan tasks:check-delayed
```

### Option 2: For Production (Railway/Server)

#### On Railway:
1. Add a **separate worker service** in Railway
2. Set command: `php artisan schedule:work`
3. This will run continuously and check every 30 minutes

#### On Traditional Server:
Add to crontab:
```bash
* * * * * cd /path/to/your/app && php artisan schedule:run >> /dev/null 2>&1
```

## How It Works

1. **Task Created**: When you create a task with 1 hour standard time
2. **Task Starts**: Automatically starts when created
3. **Automatic Check**: The scheduler checks every 5 minutes automatically
4. **After 1 Hour**: When task exceeds 1 hour, email is sent immediately (within 5 minutes)
5. **Reminders**: Email sent every 1 hour until task is completed

## Testing Steps

1. Create a task with **0.1 hours** (6 minutes) for quick testing
2. Wait 7-8 minutes
3. Run: `php artisan tasks:check-delayed`
4. Check your email inbox

## Debugging

Check if there are in-progress tasks:
```bash
php artisan tinker
>>> \App\Models\TimeManagement::where('status', 'in_progress')->get();
```

Check logs:
```bash
tail -f storage/logs/laravel.log
```

## Common Issues

### Issue: "Found 0 in-progress task(s)"
- Task might be completed already
- Check task status in the system
- Create a new test task

### Issue: "No email found for employee"
- Employee must have an email address
- Check employee record in database

### Issue: Email not sending
- Check mail configuration in `.env`
- For Gmail, use App Password
- Check `storage/logs/laravel.log` for errors

