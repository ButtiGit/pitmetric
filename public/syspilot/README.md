# SysPilot private beta (inside PitMetric)

Open https://pitmetric.it/syspilot/ once deployed. This directory contains
static assets only; Laravel handles authenticated operations and the OpenAI call.

In Coolify, set server-side environment variables:

    SYSPILOT_ALLOWED_EMAILS=your-pitmetric-login@example.com
    SYSPILOT_OPENAI_API_KEY=your-api-key
    SYSPILOT_OPENAI_MODEL=gpt-4o-mini

Never commit the real API key or put it in frontend files. The allowlist is
empty by default: only verified PitMetric users whose login email is explicitly
listed can call the API. User interventions are private to their creator.

The existing Composer deploy script runs php artisan migrate --force. If
migrations are disabled in your deployment, run that command once manually.
If Laravel config is cached, redeploy after setting env variables.

Source code: app/Http/Controllers/SysPilotController.php,
app/Http/Middleware/EnsureSyspilotAccess.php,
app/Services/SysPilot/ChecklistGenerator.php and config/syspilot.php.

Do not enter actual customer passwords, personal data or sensitive logs during
this beta. No commands are executed automatically. Browser Print / Save PDF
provides the report export.
