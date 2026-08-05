# AI Assistant Setup

In-app floating chat for logged-in users: **how-to help** + **read-only inventory questions**.

## Environment variables

Set these in local `.env` and in **Laravel Cloud → Environment → Variables**:

```env
AI_ASSISTANT_ENABLED=true
OPENAI_API_KEY=sk-your-key-here
OPENAI_MODEL=gpt-4o-mini
```

Optional:

```env
OPENAI_BASE_URL=https://api.openai.com/v1
```

To disable quickly without removing the key:

```env
AI_ASSISTANT_ENABLED=false
```

After changing Cloud env vars:

```bash
php artisan config:clear
```

## Behaviour

- Visible on main app pages (layout chat bubble, bottom-right).
- All authenticated users can ask help questions and high-level counts.
- Admins can also look up an asset by ID/serial and entity summaries.
- No writes, approvals, or email sending via AI.
- Chat endpoint is rate-limited (`20` requests/minute).

## Quick test

1. Log in to the app.
2. Open the robot chat bubble.
3. Ask: `How do I send a PR for approval?`
4. Ask (admin): `How many available desktops are there?`

If the key is missing, the chat shows: **AI Assistant is not configured**.
