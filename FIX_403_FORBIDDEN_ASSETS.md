# 🔧 Fix 403 Forbidden Error When Saving Assets

## Problem
Getting **403 Forbidden** error from nginx when clicking "Save Asset" on `/assets/create`.

## Root Cause
A 403 error from nginx means the request is blocked **before** it reaches Laravel. Common causes:

1. **File upload size limit exceeded** (most common)
2. **POST body size limit exceeded**
3. **CSRF token issue** (less likely, form has @csrf)
4. **Nginx configuration blocking POST requests**

---

## ✅ Quick Fixes

### Fix 1: Check File Upload Size

The form allows file uploads up to 10MB (`max:10240` KB). If your file is larger:

**Option A: Reduce file size**
- Compress the image/PDF before uploading
- Use a smaller file

**Option B: Check nginx limits**

In Laravel Cloud, nginx might have a `client_max_body_size` limit. This is usually configured by Laravel Cloud, but you can check:

1. Go to Laravel Cloud → Your Site → Settings
2. Look for "Nginx Configuration" or "Server Settings"
3. Check `client_max_body_size` (should be at least 10M)

---

### Fix 2: Check CSRF Token

The form has `@csrf`, but verify:

1. **Clear browser cache and cookies**
2. **Try in incognito/private window**
3. **Check browser console** for JavaScript errors

---

### Fix 3: Check Laravel Logs

Even though it's a 403, check if Laravel sees the request:

In **Laravel Cloud → Logs**, look for:
- Any errors related to `/assets` POST request
- CSRF token errors
- File upload errors

---

### Fix 4: Test Without File Upload

Try submitting the form **without** uploading a file:

1. Fill out all required fields
2. **Don't upload an invoice file**
3. Submit the form

**If it works without file:** The issue is file upload size limit.

**If it still fails:** The issue is something else (CSRF, route, etc.)

---

## 🔍 Debugging Steps

### Step 1: Check Browser Network Tab

1. Open browser developer tools (F12)
2. Go to **Network** tab
3. Submit the form
4. Look for the POST request to `/assets`
5. Check:
   - **Status code** (should be 200, not 403)
   - **Request headers** (check if CSRF token is sent)
   - **Request payload size**

### Step 2: Check Request Size

If the request is very large:
- Reduce file size
- Remove unnecessary form data
- Check if there are too many feature fields

### Step 3: Verify Route

Check if the route exists:
```bash
php artisan route:list | grep assets.store
```

Should show:
```
POST   assets  ................ assets.store › AssetController@store
```

---

## 🎯 Most Likely Solutions

### Solution 1: File Too Large (90% of cases)

**If uploading a file:**
- Reduce file size to under 5MB
- Or compress the file before uploading

**If not uploading a file:**
- The issue is something else (see other solutions)

### Solution 2: Clear Caches

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Solution 3: Check Database Tables

The validation uses `exists:asset_categories,id` and `exists:brands,id`. If these tables don't exist, validation might fail.

Run:
```bash
php artisan migrate --force
```

---

## ✅ After Fixing

1. **Test saving an asset:**
   - Fill out the form
   - Submit (with or without file)
   - ✅ Should see success message
   - ✅ Asset should appear in list

2. **If still getting 403:**
   - Check Laravel Cloud logs for more details
   - Try submitting without file upload
   - Check browser console for errors

---

## 🚀 Quick Command Summary

```bash
# 1. Clear all caches
php artisan optimize:clear

# 2. Check routes
php artisan route:list | grep assets

# 3. Run migrations (if needed)
php artisan migrate --force

# 4. Check logs
# Go to Laravel Cloud → Logs tab
```

---

## 📋 Checklist

- [ ] File size is under 10MB (if uploading)
- [ ] Tried submitting without file upload
- [ ] Cleared browser cache/cookies
- [ ] Checked browser console for errors
- [ ] Checked Laravel Cloud logs
- [ ] Cleared Laravel caches
- [ ] Verified routes exist
- [ ] Database tables exist (asset_categories, brands, assets)

---

## 💡 If Still Not Working

1. **Enable debug mode temporarily:**
   - Set `APP_DEBUG=true` in environment variables
   - This will show more detailed errors
   - **Remember to set back to `false` after fixing!**

2. **Check nginx error logs:**
   - In Laravel Cloud, check if there are nginx-specific logs
   - Look for 403 errors with details

3. **Test with a simple form:**
   - Create a minimal test form to isolate the issue
   - This helps identify if it's form-specific or general POST issue
