# Update Studio production setup

The public `/updates` page is designed to remain readable during a rolling deploy, but Update Studio requires the latest database migration before it can create localized/media posts.

After deploying a release that includes Update Studio, run in the Coolify application terminal:

```sh
php artisan migrate --force --no-interaction
php artisan storage:link
php artisan optimize:clear --except=cache
php artisan optimize
```

Set the runtime variable `PITMETRIC_EDITOR_EMAILS` in Coolify to a comma-separated list of trusted account email addresses. Do not commit real editor email addresses to the public repository.

For uploaded images/videos to survive redeploys, mount persistent storage at:

```text
/app/storage/app/public
```

External image/video URLs do not require persistent application storage.
