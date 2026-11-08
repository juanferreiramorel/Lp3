# Deploying TAP ERP

The production stack is `compose.prod.yaml`, deployed through Dokploy as a
"Compose" service. Two containers: `app` (nginx + php-fpm under supervisor)
and `db` (PostgreSQL 16). Only `app` joins `dokploy-network`, so the database
is reachable from inside the stack and nowhere else.

## Before the first deploy

Dokploy clones the repository through its GitHub App. The app must have
access to this repository, which is granted once from GitHub:

> Settings → Applications → the Dokploy app → Configure → Repository access

Without it the deploy fails at clone time with no useful message.

## Dokploy service

Create a Compose service in the project with:

| Field | Value |
|---|---|
| Source | GitHub, this repository, branch `main` |
| Compose path | `./compose.prod.yaml` |
| Domain | the host, service `app`, port `80`, Let's Encrypt |

## Environment

Dokploy injects these into the compose. The stack refuses to start when one of
the required ones is missing, rather than booting with an empty value.

| Variable | Required | Notes |
|---|---|---|
| `APP_KEY` | yes | `php artisan key:generate --show`. Sessions and encrypted columns depend on it: changing it later invalidates both. |
| `APP_URL` | yes | `https://<host>`. Wrong here means wrong links in PDFs and e-mail. |
| `DB_DATABASE` | yes | Creates the database on first boot. |
| `DB_USERNAME` | yes | |
| `DB_PASSWORD` | yes | Generate one. Never the local `rootpassword`. |
| `APP_NAME` | no | Defaults to `TAPERP`. |
| `PEOPLE_API_BASE` | no | Defaults to `https://people.lanzapy.com`. |
| `PEOPLE_API_JWT` | no | A token **issued** by the service, not its `JWT_SECRET`. Without it the CI/RUC lookup reports it is not configured and the form still takes data by hand. |
| `PEOPLE_API_TIMEOUT` | no | Seconds, defaults to 8. |

`APP_ENV`, `APP_DEBUG` and the database host are fixed in the compose file:
production never runs with debug on, and the host is always the `db` service.

## What survives a redeploy

Every redeploy rebuilds the image, so anything written inside the container is
lost unless it is on a volume. These are:

| Volume | Path | Holds |
|---|---|---|
| `taperp_pgdata` | `/var/lib/postgresql/data` | the database |
| `taperp_img` | `public/img` | product, category and user images |
| `taperp_uploads` | `public/uploads` | uploaded files |
| `taperp_archivos` | `public/archivos` | generated documents |
| `taperp_storage_app` | `storage/app` | Laravel's own file storage |

Sessions and logs are deliberately not on volumes: a redeploy signs everyone
out, which is expected, and logs go to the container's output.

## Migrating an existing database

The schema travels in the dump, and the dump includes the `migrations` table,
so `artisan migrate` is **not** run on deploy and will not fight the restore.

Dump from the source, excluding ownership and grants, since the production
role has a different name:

```
pg_dump -U <user> --no-owner --no-privileges <database> > taperp.sql
```

Restore into the running stack:

```
docker exec -i <stack>-db-1 psql -U <DB_USERNAME> -d <DB_DATABASE> < taperp.sql
```

Then check it, rather than trusting a silent exit: compare the table count and
the row count of each table against the source, and check that the sequences
carried their values (`SELECT last_value FROM clientes_id_cliente_seq`). A
restore that reports no errors can still have left a sequence at 1, and the
first insert afterwards collides.

Uploaded files are copied separately, into the volumes listed above:

```
docker cp public/img/.        <stack>-app-1:/var/www/html/public/img/
docker cp public/uploads/.    <stack>-app-1:/var/www/html/public/uploads/
docker cp storage/app/.       <stack>-app-1:/var/www/html/storage/app/
docker exec <stack>-app-1 chown -R www-data:www-data \
    /var/www/html/public/img /var/www/html/public/uploads /var/www/html/storage/app
```

The ownership step matters: files arriving as root are unreadable by php-fpm,
which shows up as broken images rather than as an error.

## Checking a deploy

```
docker exec <stack>-app-1 php artisan about --only=environment
docker exec <stack>-app-1 php -r 'echo strlen((string) @file_get_contents("http://127.0.0.1/login")), "\n";'
```

The first must report `production` with debug off. The second must print a
size, not `0`.
