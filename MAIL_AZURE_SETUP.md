# Send mail via Microsoft Azure (Graph API)

When your Microsoft 365 tenant has **Basic Authentication disabled** for SMTP, you can send email using **Microsoft Graph API** with an Azure AD app. No SMTP password needed.

## 1. Register an app in Azure

1. Go to [Azure Portal](https://portal.azure.com) → **Microsoft Entra ID** (or Azure Active Directory) → **App registrations** → **New registration**.
2. Name it (e.g. "Laravel Asset Mail"), choose **Accounts in this organizational directory only**, click **Register**.
3. On the app page, note:
   - **Application (client) ID** → use as `MICROSOFT_GRAPH_CLIENT_ID`
   - **Directory (tenant) ID** → use as `MICROSOFT_GRAPH_TENANT_ID`
4. Go to **Certificates & secrets** → **New client secret** → add description, choose expiry → **Add**. Copy the **Value** once (it’s hidden later) → use as `MICROSOFT_GRAPH_CLIENT_SECRET`.

## 2. Grant permission to send mail

1. In the app, go to **API permissions** → **Add a permission**.
2. Choose **Microsoft Graph** → **Application permissions**.
3. Search for **Mail.Send**, select it, click **Add permissions**.
4. Click **Grant admin consent for [Your org]** so the app can send mail.

## 3. Restrict sending to the helpdesk mailbox (recommended)

So the app can only send as `helpdesk@tanseeqinvestment.com`:

1. In Azure, open **Microsoft Entra ID** → **Enterprise applications** → your app → **Permissions**.
2. Or in Exchange Admin Center: create an **Application access policy** that allows this app to send only as the helpdesk user.  
   (Optional; if skipped, the app can send as any user in the tenant.)

## 4. Configure Laravel

In `.env` set:

```env
MAIL_MAILER=microsoft-graph
MICROSOFT_GRAPH_CLIENT_ID=your-client-id-from-step-3
MICROSOFT_GRAPH_CLIENT_SECRET=your-client-secret-value
MICROSOFT_GRAPH_TENANT_ID=your-tenant-id
MAIL_FROM_ADDRESS=helpdesk@tanseeqinvestment.com
MAIL_FROM_NAME="Tanseeq Asset Management"
```

Then run:

```bash
php artisan config:clear
php artisan mail:test --to=someone@tanseeqinvestment.com
```

## 5. If the package is not installed

If `composer require innoge/laravel-msgraph-mail` failed (e.g. file lock), run:

```bash
composer install
```

Then complete the steps above.
