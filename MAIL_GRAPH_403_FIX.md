# Fix 403 "Access is denied" when sending mail via Microsoft Graph

If `php artisan mail:test --to=...` returns **403** and:

```text
"error":{"code":"ErrorAccessDenied","message":"Access is denied. Check credentials and try again."}
```

your Azure app has valid credentials but **is not allowed to access the mailbox** (helpdesk@tanseeqinvestment.com). Microsoft 365 often blocks this until you add an **Exchange application access policy**.

---

## 1. Confirm Azure (admin consent)

1. Go to [Azure Portal](https://portal.azure.com) → **Microsoft Entra ID** → **App registrations** → your app.
2. Open **API permissions**.
3. Ensure **Mail.Send** (Application) is listed and **Status** is **Granted for [Your org]**.
4. If not, click **Grant admin consent for [Your organization]** and confirm.

---

## 2. Allow the app to use the helpdesk mailbox (required)

Application permission **Mail.Send** is not enough by itself. You must allow this app to access the **helpdesk** mailbox via an **Exchange application access policy**. This is done in **Exchange Online**, not in the Azure Portal.

### Option A: Exchange Online PowerShell (recommended)

Someone with **Exchange admin** (or Global admin) in your tenant needs to run this **once**:

1. Install the **Exchange Online PowerShell** module (if needed):
   ```powershell
   Install-Module -Name ExchangeOnlineManagement -Scope CurrentUser
   ```
2. Connect:
   ```powershell
   Connect-ExchangeOnline
   ```
   Sign in with an admin account (e.g. admin@tanseeqinvestment.com).

3. Create a policy that allows **only your app** to send as **helpdesk@tanseeqinvestment.com**. Replace `bf41ba20-f939-4996-82d5-90d913f98cac` with your app's **Application (client) ID** from Azure if different:

   ```powershell
   New-ApplicationAccessPolicy -AppId "bf41ba20-f939-4996-82d5-90d913f98cac" -PolicyScopeGroupId "helpdesk@tanseeqinvestment.com" -AccessRight RestrictAccess -Description "Laravel send as helpdesk"
   ```

4. Disconnect when done:
   ```powershell
   Disconnect-ExchangeOnline
   ```

After this, the app can send mail as helpdesk@tanseeqinvestment.com. Run again:

```bash
php artisan config:clear
php artisan mail:test --to=helpdesk@tanseeqinvestment.com
```

### Option B: Exchange admin center (if available)

If your tenant uses the **Exchange admin center** ([admin.exchange.microsoft.com](https://admin.exchange.microsoft.com)):

1. Sign in with an account that has Exchange admin rights.
2. Go to **Mail flow** → **Application access policy** (or similar).
3. Create a new policy:
   - **Application ID:** `bf41ba20-f939-4996-82d5-90d913f98cac` (your Azure app's Client ID).
   - **Scope:** restrict to the user **helpdesk@tanseeqinvestment.com** (or the mailbox you use in `MAIL_FROM_ADDRESS`).
   - **Access right:** allow send (wording may vary).

Save the policy and test again with `php artisan mail:test`.

---

## 3. If you don't have Exchange admin

- Ask your **Microsoft 365 or Exchange administrator** to run the **New-ApplicationAccessPolicy** command above (Option A) once.
- Or use **SMTP** with helpdesk's password or App password instead of Graph (see `MAIL_GMAIL_TO_OUTLOOK.md`, Option 1).

---

## Summary

| Cause of 403 | Fix |
|--------------|-----|
| Admin consent not granted | In Azure → App → API permissions → **Grant admin consent** for Mail.Send. |
| App not allowed to use mailbox | In Exchange (PowerShell or EAC), add an **application access policy** allowing your app (Client ID) to send as helpdesk@tanseeqinvestment.com. |

Your app's Client ID from `.env` is: **bf41ba20-f939-4996-82d5-90d913f98cac**. Use this in the policy.
