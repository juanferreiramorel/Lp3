# TAP ERP

An ERP for small businesses in Paraguay: sales and invoicing under SET
timbrados, simple receipts, cash registers, purchases, stock, receivables and
payables, and a client book that fills itself in from a CI or RUC.

The interface is in Spanish, because the people who use it are.

## What it covers

| Area | Modules |
|---|---|
| Sales | Sales, credit and debit notes, simple receipts, orders, cash count (arqueo) |
| Clients | Clients with CI/RUC lookup, accounts receivable |
| Purchases | Purchases, purchase orders, suppliers, accounts payable |
| Stock | Products, stock, brands |
| Invoicing | Timbrados (number, expedition point, range, validity), taxes, payment methods |
| Organisation | Companies, branches, cash registers, departments, cities, roles and permissions |
| Reports | Sales ledger (Libro Venta), sales, purchases, orders, clients, suppliers, products, audit log |

## Stack

- PHP 8.2, Laravel 10
- PostgreSQL (16 in production; the development compose still runs 9.5)
- AdminLTE 3 and Bootstrap 4, assets built with Vite
- Docker: nginx and php-fpm in one container for production

## Running it locally

Requirements: Docker with Compose, and Node 20 if you want to rebuild the
front-end assets outside the container.

```bash
git clone https://github.com/juanferreiramorel/Lp3.git taperp
cd taperp
cp .env.example .env
docker compose up -d
docker compose exec app php artisan key:generate
```

The app answers on http://localhost:8000 and pgAdmin on http://localhost:5050.

### The database

Migrations alone do not build a working database. The core tables (clients,
products, sales, purchases, cash registers and others) were created with the
SQL scripts in [`sql/`](sql/), and the migrations in `database/migrations`
only add what came after. To start from a usable state, restore a dump of an
existing installation; [`docs/DEPLOY.md`](docs/DEPLOY.md) shows how to take
one and load it. Turning the scripts into migrations so that
`php artisan migrate` works from an empty database is the most useful
contribution this project could receive.

### CI and RUC lookup

Typing a CI or a RUC on the client form fills in the name, surname and birth
date from an external people database. It is optional: without
`PEOPLE_API_BASE` and `PEOPLE_API_JWT` in `.env` the form simply asks for
everything by hand.

| Variable | Purpose |
|---|---|
| `PEOPLE_API_BASE` | Base URL of the people service |
| `PEOPLE_API_JWT` | Token issued by that service |
| `PEOPLE_API_TIMEOUT` | Seconds before giving up, 8 by default |

## Deploying

The production stack is [`compose.prod.yaml`](compose.prod.yaml), deployed
through Dokploy. [`docs/DEPLOY.md`](docs/DEPLOY.md) has the environment
variables, what survives a redeploy, and how to move an existing database and
its uploaded files.

## License

TAP ERP is open source under the MIT Expat license, with the same open-core
structure that [Chatwoot](https://github.com/chatwoot/chatwoot) uses: anything
placed in an `enterprise/` directory would be governed by its own license in
`enterprise/LICENSE`. That directory does not exist today, so all of the code
in this repository is MIT. See [`LICENSE`](LICENSE).
