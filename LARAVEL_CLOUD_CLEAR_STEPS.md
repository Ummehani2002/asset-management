# Laravel Cloud – Clear Steps (Fix 504 & Stuck Deployment)

Follow these in order.

---

## Part 1: Fix Laravel Cloud settings

### Step 1: Open your project in Laravel Cloud

1. Go to **https://cloud.laravel.com** and sign in.
2. Click **Tanseeq Investment LLC** → **asset-management** → **main**.

### Step 2: Set Node version

1. Click the **Settings** tab.
2. Find **Node version** (or **Build environment**).
3. Set it to **20** (or **18** if 20 is not available).
4. Save if there is a Save button.

### Step 3: Set Build commands

1. In **Settings**, find **Build commands**.
2. Use exactly:

```bash
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci --audit false
npm run build
```

3. Save.

### Step 4: Set Deploy commands

1. In **Settings**, find **Deploy commands**.
2. Use exactly:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. Save.

---

## Part 2: Deploy again

### Step 5: Push code (trigger new deployment)

1. Open terminal in your project: `d:\sites\final_asset`
2. Run:

```bash
git add .
git status
git commit -m "Trigger deploy" --allow-empty
git push origin main
```

(Use your real branch name instead of `main` if different.)

### Step 6: Watch the deployment

1. In Laravel Cloud go to **Deployments**.
2. Wait **3–5 minutes**. Steps should change from **Pending** to running, then green.
3. If a step fails, open **Build logs** or **Deployment logs** and read the error.
4. If it stays on Pending for more than 5 minutes, click **Cancel**, then repeat from Step 5 (and double‑check Steps 2–4).

---

## Part 3: After deployment succeeds

### Step 7: Clear caches (optional but recommended)

1. In Laravel Cloud: **asset-management** → **main** → **Commands** (or Console).
2. Run:

```bash
php artisan optimize:clear
```

### Step 8: Test the site

1. Open: **https://asset-management.laravel.cloud**
2. If you still see **504**, wait 1–2 minutes and try again (sometimes the new deployment needs a moment to receive traffic).

---

## Quick checklist if something goes wrong

| Problem | What to do |
|--------|------------|
| Build stuck on Pending | Cancel deploy, check Node version (Step 2), push again (Step 5). |
| Build fails on `npm run build` | Check Build logs; often Node version wrong → set Node 20 (Step 2). |
| Deploy fails on `migrate` | Check Deployment logs; if DB error, fix DB env vars in Laravel Cloud **Environment**. |
| Site still 504 after success | Run `php artisan optimize:clear` (Step 7), wait 2 min, try again. |
| Wrong URL | Use the URL shown in Laravel Cloud for **asset-management** (e.g. asset-management.laravel.cloud). |

---

**Summary:** Set Node + Build + Deploy commands (Steps 1–4) → push to trigger deploy (Step 5) → wait for success (Step 6) → clear caches (Step 7) → test site (Step 8).
