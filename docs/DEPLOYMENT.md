# Beta Deployment with Coolify and Nixpacks

This guide covers PitMetric's beta environment. It deploys the Laravel application as one web process and now includes a small persistent public-upload area used by the Update Studio. Redis, queue workers and scheduler processes are still intentionally omitted.

## Local and beta environments

| Local development | Beta deployment |
| --- | --- |
| Uses the developer's local environment file and the existing local development workflow. | Uses Coolify-managed build and runtime variables; no environment file is copied into the image. |
| The current starter application uses SQLite by default. | Uses the separate MySQL 8 Coolify resource over the internal network. |
| Frontend development may use Vite's development server. | Nixpacks runs the existing `npm run build` command to generate production assets. |

Keep local credentials and beta secrets separate. Do not copy a local environment file to Coolify.

## Coolify architecture

```text
Internet
  -> Coolify HTTPS proxy
      -> PitMetric application container
          Nginx on port 80 -> PHP-FPM
          public root: /app/public
          Laravel fallback: /index.php
          persistent update media: /app/storage/app/public
      -> private Coolify network -> MySQL 8 resource
```

`nixpacks.toml` retains the Nixpacks Laravel start process, which starts only Nginx and PHP-FPM. Its conservative Nginx and PHP-FPM pool limits are intended for a beta application sharing a 2 vCPU / 4 GB server with Coolify and MySQL. There is deliberately no Supervisor configuration, Redis service, queue worker, or scheduler process.

## Coolify application setup

Create a non-static Application resource in the `beta` environment with the Git branch `main` and these settings:

- Build Pack: Nixpacks
- Base Directory: `/`
- Ports Exposes: `80`
- Health check path: `/up`

The committed `nixpacks.toml` supplies the web port, `/app/public` document root, `/index.php` fallback, production asset build, and process limits. Leave the deployment command empty: do not use `composer run setup`, `php artisan migrate`, or any worker command during image build.

Configure `NIXPACKS_NODE_VERSION` as a Coolify build variable before the first deployment. Use a supported Node 22 release: the checked-in Vite toolchain requires a newer Node release than Nixpacks' default Node 18.

## Required environment variable names

Store runtime variables in Coolify's encrypted environment-variable store. The lists below intentionally contain names only; do not commit values, credentials, or an application key.

| Purpose | Variable names |
| --- | --- |
| Application | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` |
| PitMetric publishing | `PITMETRIC_EDITOR_EMAILS` |
| Logging | `LOG_CHANNEL`, `LOG_LEVEL` |
| MySQL | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Mail (Resend) | `MAIL_MAILER`, `RESEND_KEY`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` |
| Session and cache | `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE`, `CACHE_STORE` |
| Queue | `QUEUE_CONNECTION` |
| Filesystem | `FILESYSTEM_DISK` |
| Nixpacks build | `NIXPACKS_NODE_VERSION` |

`PITMETRIC_EDITOR_EMAILS` is a comma-separated allow-list of account email addresses that may open `/studio/updates` and publish public development posts. Keep it restricted to trusted accounts; normal registered users receive a 403 response for those routes.

Use the Resend mail transport for beta: password-reset and email-verification messages must be deliverable. The application reads the Resend API key only from `RESEND_KEY`. `APP_URL` must match the HTTPS beta URL so that signed verification links are generated correctly. Until a queue worker is approved, configure `QUEUE_CONNECTION` for inline execution; a worker-backed connection would allow jobs to accumulate without being processed. Laravel's standard email-verification notification is synchronous and does not require a queue worker.

`PORT`, `NIXPACKS_PHP_ROOT_DIR`, and `NIXPACKS_PHP_FALLBACK_PATH` are defined in `nixpacks.toml`; they are not secrets and do not need duplicate Coolify entries.

## Persistent media for Update Studio

Update Studio can publish text-only posts, remote image/video URLs, or uploaded media files. Uploaded files use Laravel's `public` disk under `/app/storage/app/public`.

In Coolify, attach a persistent storage volume to the application and mount it at:

```text
/app/storage/app/public
```

Without that volume, uploaded images and videos can disappear after a redeploy. External media URLs are stored in the database and do not need this volume.

After the first deployment that enables Update Studio, open the Coolify terminal and run:

```sh
php artisan storage:link
```

The command creates `/app/public/storage` pointing to the persistent public disk. Run it again only if the symlink is missing in a new container.

## MySQL internal networking

Create the MySQL 8 resource in the same Coolify destination as the application. Set `DB_HOST` to the resource's internal hostname or internal connection endpoint supplied by Coolify, not to the Hetzner host IP, `localhost`, or a public database address. Keep the MySQL port private and do not publish it to the Internet.

The application currently uses database-backed session and cache stores by default, so the first migration is required before normal authenticated beta use. The migrations also create queue-related tables, but no queue worker is started by this deployment.

## First deployment

1. Create and back up the MySQL resource, then create the Coolify application resource with the settings above.
2. Add the required variable names in Coolify. Generate `APP_KEY` securely and save it only as a Coolify secret. Add your trusted account email to `PITMETRIC_EDITOR_EMAILS` if you need Update Studio.
3. Configure the persistent `/app/storage/app/public` volume if you plan to upload media directly from PitMetric.
4. Deploy from `main`. Confirm in the build log that dependency installation and `npm run build` complete, then confirm that Nginx and PHP-FPM start.
5. After the first container is healthy, open the Coolify terminal for that deployment and run:

   ```sh
   php artisan config:clear
   php artisan migrate --force --no-interaction
   php artisan storage:link
   php artisan optimize:clear --except=cache
   php artisan optimize
   php artisan pitmetric:mail-check
   ```

6. Check that the diagnostic output reports the expected effective values, the `resend` mailer and transport, a present key, the installed Resend SDK, and `CONFIG_CACHED=yes` after `optimize`. It never prints the key itself. Send a real transport check only to an address you control with `php artisan pitmetric:mail-check --send=test@example.com`.
7. Verify the `/up` health endpoint, HTTPS redirects, registration, login, password reset, email delivery, the email-verification link flow, and `/studio/updates` with an allowed editor account.
8. For later releases, run the migration command manually only when that release contains a migration. Take a MySQL backup first.

## Configuration cache changes

Do not depend on runtime secrets being available during the Nixpacks build or serialize runtime configuration and secrets into an image layer. The build therefore does not run migrations or cache Laravel configuration. After changing a Coolify runtime variable, redeploy and rebuild Laravel's production caches inside the new running container:

```sh
php artisan config:clear
php artisan optimize:clear --except=cache
php artisan optimize
php artisan pitmetric:mail-check
```

The `--except=cache` option deliberately avoids flushing PitMetric's database-backed application cache. For a config-only refresh, `php artisan config:clear` followed by `php artisan config:cache` is sufficient. If these commands are later automated as Coolify post-deployment commands, check their logs because the deployment can already be marked complete before a post-deployment failure is reported.

## Rollback basics

Use Coolify to redeploy the last known-good application deployment or a known-good commit from `main`. Check the health endpoint and authentication flow after the rollback.

Application-image rollback does not reverse database changes. Do not run `migrate:rollback` automatically. Before a release with migrations, verify forward compatibility and take a MySQL backup; restore or perform a planned database recovery only when the release's migration plan requires it.

## Security and operational notes

- Keep `APP_KEY`, database credentials, mail credentials and `PITMETRIC_EDITOR_EMAILS` in Coolify runtime configuration only. Never put secret values in Git, the image, logs, or a command history.
- Keep the update editor allow-list narrow. Public registration must never imply publishing access.
- Uploaded files are restricted to common image/video extensions and 50 MB at the Laravel layer. Web-server/PHP upload limits may be lower and should be raised deliberately rather than globally disabling limits.
- Disable debug output in beta, use the HTTPS beta URL for `APP_URL`, and keep secure session-cookie settings enabled. Test signed email-verification links after each proxy or domain change.
- Restrict Coolify and server administrative access, keep Ubuntu/Coolify images updated, and verify MySQL backups and restore procedures periodically.
- Expose the application through Coolify's HTTPS proxy only. Keep MySQL on the private Coolify network and never expose its port publicly.
- The application trusts Coolify's reverse proxy. Validate HTTPS redirects, secure cookies, and verification-link redirects behind the proxy before beta access is opened.
- Use only a sender address accepted by the Resend account. A Resend-provided testing sender is suitable only within its documented test restrictions; delivery to general beta users requires a verified sender domain. Do not invent or hardcode a domain in application code.
- Keep Coolify's public domain and `APP_URL` aligned. Previously issued absolute signed links remain bound to the hostname used when generated; rebuild the configuration cache and request a new link after changing domains.
- Start with the limits in `nixpacks.toml`, monitor PHP-FPM memory/CPU use and request latency, and adjust only from observed beta traffic.

## References

- [Coolify Laravel deployment guide](https://coolify.io/docs/applications/laravel)
- [Nixpacks PHP provider](https://nixpacks.com/docs/providers/php)
- [Nixpacks configuration file reference](https://nixpacks.com/docs/configuration/file)
