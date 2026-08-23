# Beta Deployment with Coolify and Nixpacks

This guide covers PitMetric's beta environment only. It deploys the existing Laravel application as one web process; it does not add Redis, queue workers, scheduler processes, migrations during image build, or persistent application uploads.

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
| Logging | `LOG_CHANNEL`, `LOG_LEVEL` |
| MySQL | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Mail | `MAIL_MAILER`, `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` |
| Session and cache | `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE`, `CACHE_STORE` |
| Queue | `QUEUE_CONNECTION` |
| Filesystem | `FILESYSTEM_DISK` |
| Nixpacks build | `NIXPACKS_NODE_VERSION` |

Use a real mail transport for beta: password-reset and email-verification messages must be deliverable. `APP_URL` must match the HTTPS beta URL so that signed verification links are generated correctly. Until a queue worker is approved, configure `QUEUE_CONNECTION` for inline execution; a worker-backed connection would allow jobs to accumulate without being processed.

`PORT`, `NIXPACKS_PHP_ROOT_DIR`, and `NIXPACKS_PHP_FALLBACK_PATH` are defined in `nixpacks.toml`; they are not secrets and do not need duplicate Coolify entries.

## MySQL internal networking

Create the MySQL 8 resource in the same Coolify destination as the application. Set `DB_HOST` to the resource's internal hostname or internal connection endpoint supplied by Coolify, not to the Hetzner host IP, `localhost`, or a public database address. Keep the MySQL port private and do not publish it to the Internet.

The application currently uses database-backed session and cache stores by default, so the first migration is required before normal authenticated beta use. The migrations also create queue-related tables, but no queue worker is started by this deployment.

## First deployment

1. Create and back up the MySQL resource, then create the Coolify application resource with the settings above.
2. Add the required variable names in Coolify. Generate `APP_KEY` securely and save it only as a Coolify secret.
3. Deploy from `main`. Confirm in the build log that dependency installation and `npm run build` complete, then confirm that Nginx and PHP-FPM start.
4. After the first container is healthy, open the Coolify terminal for that deployment and run:

   ```sh
   php artisan migrate --force --no-interaction
   ```

5. Verify the `/up` health endpoint, HTTPS redirects, registration, login, password reset, email delivery, and the email-verification link flow.
6. For later releases, run the migration command manually only when that release contains a migration. Take a MySQL backup first.

## Rollback basics

Use Coolify to redeploy the last known-good application deployment or a known-good commit from `main`. Check the health endpoint and authentication flow after the rollback.

Application-image rollback does not reverse database changes. Do not run `migrate:rollback` automatically. Before a release with migrations, verify forward compatibility and take a MySQL backup; restore or perform a planned database recovery only when the release's migration plan requires it.

## Security and operational notes

- Keep `APP_KEY`, database credentials, and mail credentials in Coolify secrets only. Never put them in Git, the image, logs, or a command history.
- Disable debug output in beta, use the HTTPS beta URL for `APP_URL`, and keep secure session-cookie settings enabled. Test signed email-verification links after each proxy or domain change.
- Restrict Coolify and server administrative access, keep Ubuntu/Coolify images updated, and verify MySQL backups and restore procedures periodically.
- Expose the application through Coolify's HTTPS proxy only. Keep MySQL on the private Coolify network and never expose its port publicly.
- The application currently has no explicit trusted-proxy configuration. Validate HTTPS redirects, secure cookies, and verification-link redirects behind the Coolify proxy before beta access is opened; changing proxy handling is outside this deployment-only scope.
- Start with the limits in `nixpacks.toml`, monitor PHP-FPM memory/CPU use and request latency, and adjust only from observed beta traffic.

## References

- [Coolify Laravel deployment guide](https://coolify.io/docs/applications/laravel)
- [Nixpacks PHP provider](https://nixpacks.com/docs/providers/php)
- [Nixpacks configuration file reference](https://nixpacks.com/docs/configuration/file)
