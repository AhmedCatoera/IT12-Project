# SweetNest Homemade Order and Inventory Management System

A Laravel 12 web system for a home bakery. It covers customers, orders, payments,
job orders for the kitchen, and ingredient inventory (stock in, ingredients used,
adjustments and physical counts), with reports for the Owner.

## What you need

- PHP 8.2 or newer (XAMPP 8.2+ works) with these extensions turned on:
  `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, and `gd` for photo uploads
- Composer
- MySQL 8 or MariaDB (the MySQL that comes with XAMPP is fine)
- Node and npm are **optional**. The screens are styled with Tailwind CSS and the
  interactive parts (order form, address maps, stock forms, payment form, sales
  chart) are React components, but the compiled files are already in
  `public/build`, so the system runs with XAMPP alone. You only need Node (20 or
  newer) if you want to change the design or the React code, see "Changing the
  front end" below.
- Internet is only needed for the map tiles (OpenStreetMap). Everything else,
  including the Inter font and Leaflet, is bundled.

## First-time setup

Open a terminal in the project folder, then:

```bash
composer install
copy .env.example .env        # on Mac or Linux: cp .env.example .env
php artisan key:generate
```

Create an empty database called `sweetnest_oims` in phpMyAdmin. If your MySQL
user isn't `root` with no password, change `DB_USERNAME` and `DB_PASSWORD` in `.env`.

Then pick one of these:

- **Recommended:** `php artisan migrate --seed`
  This creates every table and fills in sample data dated around today.
- **Or** import `database/sweetnest_oims.sql` in phpMyAdmin. It is a snapshot of
  the same data, dated around October 11, 2026.

Start the system:

```bash
php artisan serve
```

Open http://localhost:8000 and log in.

| Account | Email | Password |
|---|---|---|
| Owner | owner@sweetnest.com | password |
| Staff | staff@sweetnest.com | password |

To start over with fresh sample data at any time: `php artisan migrate:fresh --seed`
(this erases everything in the database).

## Who can do what

| | Owner | Staff |
|---|:---:|:---:|
| Customers: add, edit, deactivate | ✓ | ✓ |
| Orders: create, edit details, cancel with a reason | ✓ | ✓ |
| Record payments | ✓ | ✓ |
| Job orders: create, start, mark done, cancel, reopen | ✓ | ✓ |
| Log ingredients used on a job order (stock out) | ✓ | ✓ |
| View stock levels and stock history | ✓ | ✓ |
| Physical count (count what is on the shelf) | ✓ | ✓ |
| Apply a count's corrections to stock | ✓ | |
| Stock in (one receipt, many ingredients) | ✓ | |
| Adjustments (spoilage, expired, damaged, entry corrections) | ✓ | |
| Fix or remove a wrong "ingredient used" line | ✓ | |
| Add or edit ingredients and products | ✓ | |
| Reports and user accounts | ✓ | |

Nothing is ever deleted: customers and users are deactivated, and orders are
cancelled with a reason.

## Running the tests

```bash
php artisan test
```

The 156 tests use a temporary in-memory SQLite database (needs the `pdo_sqlite`
extension), so they never touch your real data.

## Changing the front end

The source files are in `resources/css/app.css` (Tailwind, with the colours and
reusable pieces) and `resources/js` (the React components, plus `ui.js` for the
small bits like the sidebar and modals). Vite compiles them into `public/build`.

```bash
npm install
npm run dev      # while editing: rebuilds on every save
npm run build    # when done: writes the final files to public/build
```

Commit `public/build` after `npm run build` so the next person doesn't need Node.
Blade views reference the compiled files through `@vite(...)` in
`resources/views/layouts/app.blade.php`.

## Good to know

- **Map:** customer and delivery addresses have a map that opens on Davao City
  (pins can be anywhere in the Philippines). Clicking it (or searching a place) fills in the address
  from OpenStreetMap. Address lookups go through the system (`/geo/reverse` and
  `/geo/search`), which sends a proper User-Agent as OpenStreetMap asks. Optional
  settings in `.env`: `NOMINATIM_URL`, `NOMINATIM_USER_AGENT`, `MAP_TILE_URL`.
- Time zone is Asia/Manila (`APP_TIMEZONE` in `.env`).
- Order totals, amounts paid, payment status, stock on hand and count differences
  are never stored. They are calculated from the order items, payments and stock lines.
- Reference photos are saved in `storage/app/public/order-references` and GCash
  screenshots in `storage/app/public/payment-proofs`. Both are shown through the
  system, so `php artisan storage:link` isn't needed. The size limit is 4 MB, or
  lower if PHP's `upload_max_filesize` is lower.
- `public/build` is committed so XAMPP alone runs it; it holds two stylesheets (the
  app's and Leaflet's map styles) plus the script and fonts. Keep all of them.
- `config/sweetnest.php` holds the shop's address (the default pickup spot), the
  delivery city, the rush rule (due within how many days of the order date), the
  default rush fee and its quick choices, the refund share on a cancelled fully paid
  order, and the cake choices offered when ordering (type of cake, flavors, icing
  flavors, sizes, tiers). Edit that file to change any of them.
- See `CHANGES.md` for everything that changed from the first version and why.
