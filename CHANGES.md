# What changed in this version

This version rebuilds SweetNest around the options agreed on after the review:
two roles only (Owner and Staff), stock on hand calculated instead of stored, a
products table, Stock In and Adjust Stock for the Owner only, orders cancelled
instead of deleted, and customers deactivated instead of deleted. It also fixes
every bug found in the review, normalizes the database, and replaces the whole
interface. How to run it is in `README.md`.

## Update: Ahmed's clarifications, round 7 (October 11, evening)

Ahmed's answers to the round-6 questions changed several rules. The database gained
`orders.delivery_fee` and `order_items.tiers`, so **rebuild an older database once**
(`php artisan migrate:fresh --seed` or re-import `database/sweetnest_oims.sql`).

- **Regular or rush comes from the dates, not a choice.** An order due within 1 day of
  the day it was placed (tomorrow or today) is a rush order; due 2 or more days later
  is regular (`rush_within_days` in `config/sweetnest.php`). The New order form shows
  the type as soon as the due date is set, with the fee box (40% default, 45% one
  click, any number above 0) only for rush. Edit details re-checks the type when the
  due date moves; a date that would flip the type is refused once a payment was
  recorded, and so is a rush fee change.
- **Rush orders are paid in full to confirm.** The "Awaiting payment" step asks for
  the whole total on a rush order (the Record payment pop-up says so); a regular
  order still confirms on the 50% down payment, and its job order can be created on
  that. The status label is now "Awaiting payment" rather than "Awaiting down payment".
- **Refund only on a fully paid order.** Cancelling a fully paid order gives back 50%
  of the total; an order that only paid its down payment gets nothing (the shop keeps
  the down payment). The cancel pop-up says which case applies before confirming.
- **Ingredients used are logged once the job order is done.** "Log ingredients used"
  appears after "Mark as done" (not while baking), and the order's Next line points
  to it.
- **Pickup at the customer's spot.** A pickup order carries a pickup spot: the shop
  by default (`shop.address` in `config/sweetnest.php`: Lot 14 Block 5, Sampaguita
  St., Brgy. Santo Niño, Tugbok District, Davao City; the map pin is approximate), or a
  meet-up place the customer asks for, picked on the map like a delivery address.
  The order page, job order sheet and printed slip show it, with directions and a
  map pin for a meet-up spot. The spot is stored in the same `delivery_*` columns a
  delivery uses.
- **Delivery only around Davao City.** A delivery address outside `delivery_city`
  (typed or the customer's saved one) is refused with a note to choose pickup; the
  form warns as soon as such an address shows. Customers from anywhere can still
  order for pickup.
- **Delivery fee.** Delivery orders have a "Delivery fee" box for what the delivery
  app (Maxim) quotes; it is its own line on the order, the printed slip and the
  Payments tab, part of the total and the down payment, and counted in the Sales
  report. New column `orders.delivery_fee`.
- **Cake tiers.** Cake details gain "Tiers" (1, 2 or 3; `cake_tiers` in the config),
  shown on the order and the job order sheet. New column `order_items.tiers`.
- **Live search.** Every search box (orders, job orders, customers, inventory,
  payments, stock in) filters the list as you type; the address bar follows, so
  the page can still be bookmarked or refreshed.
- **Fix: the map styles were missing from the two October 11 zips.** The build has a
  second stylesheet (`public/build/assets/app-CLLlcC51.css`, Leaflet's own styles,
  listed in the manifest's `css` entry for `app.jsx`); it had been removed by mistake,
  so map tiles spilled over the form. Restored; don't delete it when tidying `public/build`.
- 156 tests, all passing on SQLite and on MySQL/MariaDB.

## Update: Ahmed's feedback, round 6 (October 11)

- **Regular or rush orders.** Every order has a type. Regular is as before: 50% down
  payment to confirm, or pay in full. Rush adds a fee on top of the items, a percent
  the Owner or Staff sets when taking the order (40% by default, 45% one click away,
  any number above 0) and can change later in Edit details, until the first payment
  is recorded: from then on the type and fee are fixed, because the down payment and
  balance were worked out from them. The fee shows as its own
  line on the order, the printed slip and the Payments tab; the down payment and the
  balance follow the total with the fee, and the Sales report counts it. New columns
  `order_kind` and `rush_fee_percent` on `orders`.
- **Cancelling refunds half of what was paid.** The cancel pop-up shows the refund
  before confirming. The cancelled order shows "Refund due" with a "Refund given"
  button to press once the money is handed back, and the Collections report has a
  "Refunds given" tile. The share is `refund_percent` in `config/sweetnest.php`; new
  columns `refund_amount` and `refunded_at` on `orders`.
- **Bread is a product category** (with sample breads). No extra fields, like pastry.
- **Pickup orders show the shop's address** on the order page, the New order form and
  the printed slip. It is `shop.pickup_address` in `config/sweetnest.php` (placeholder
  until SweetNest's real address is in).
- **Custom items.** Press *Custom* on an item row to type an item that is not on the
  price list: its name, what kind it is (a custom cake gets the cake boxes) and the
  agreed price. It saves, prints and reports like any item ("Custom item" is marked).
  `order_items.product_id` is now optional, with `custom_name` and `custom_category`.
- **Quieter delivery address.** Choosing Delivery shows the customer's saved address
  as plain text with an *Edit* button; the map and fields only open when pressed (or
  right away if the customer has no saved address yet).
- `database/sweetnest_oims.sql` regenerated (sample data dated around October 11).
  **Rebuild an older database once:** `php artisan migrate:fresh --seed` or re-import
  the SQL file.
- 149 tests, all passing on SQLite and on MySQL/MariaDB.

## Update: Ahmed's feedback, round 5 (October 7)

- **Cake details come from dropdowns.** When the product is a cake, the item has a
  *Type of cake* (Sponge (plain), Moist (plain), Chiffon), a flavor and a size, all
  chosen from lists in `config/sweetnest.php`. Chiffon cakes pick a cake flavor (ube,
  orange, strawberry, chocolate, mocha, mango); sponge and moist cakes are flavored by
  the icing, so their flavor box is labelled *Icing flavor* and uses the icing list
  (a placeholder list until SweetNest's own is in). Sizes: 3x2 (bento cake), 6x6, 6x5,
  7x7, 7x5, 8x8. Design, message and the reference photo stay as they were. The type
  is a new `cake_type` column on `order_items`.
- **Pastries and souvenirs have no extra fields.** Only cakes show the type, flavor,
  size, design, message and photo boxes. Anything special for a mug or a box of
  brownies goes in the order notes.
- **A chosen reference photo can be removed again** with the Remove button next to
  the file box, on the New order form and in the Edit item pop-up (which also shows
  the current photo with a "Remove the current photo" tick).
- **Payments are Cash or GCash only.** Bank transfer and Other are gone. Cash needs
  no proof. GCash needs the reference number or a screenshot of the payment, one of
  the two is enough; the screenshot is a new `proof_image` column on `payments` and
  opens from the Payments tab, the payments list and the Collections report.
- **A job order can't be scheduled after the order is due.** The production date is
  capped at the due date (or today, if the order is already late) in the pop-up and
  on the server.
- The how-to banner on the job order page is gone (only a cancelled job order still
  shows a notice). The "Fix" button on ingredients used is now "Edit".
- The "My location" button was removed from the address maps; search and clicking the
  map remain.
- Contact numbers must be exactly 11 digits (mobile numbers like 0917 123 4567).
- `database/sweetnest_oims.sql` regenerated with the new columns and sample data dated
  around October 7, 2026. **If you already have a database from an earlier version,
  rebuild it:** `php artisan migrate:fresh --seed` (or re-import the SQL file), because
  the new columns live in the original table migrations.
- 141 tests, all passing on SQLite and on MySQL/MariaDB.

## Update: Tailwind CSS and React (October 5)

The team's paper lists React and Tailwind CSS as the technology stack, so the
front end now uses both. The screens look and work the same as before.

- **Tailwind CSS replaces the hand-written stylesheet.** `public/css/app.css` is
  gone. The colours, font sizes and the reusable pieces (buttons, fields, cards,
  tables, badges, tabs, modals, the progress steps) are defined once in
  `resources/css/app.css` with Tailwind's `@theme` and component layers, and every
  Blade view is styled with Tailwind utility classes. Vite compiles it into one
  small CSS file in `public/build`.
- **React runs the interactive parts.** The parts that change while you type are
  React components in `resources/js/components`: the New order form (customer
  finder, pickup or delivery, item rows with price autofill and the live total),
  the address picker with the map (used by Add/Edit customer, the delivery address
  and Edit order), the read-only map on customer and order pages, the Stock in,
  Adjustment and Ingredients used forms (ingredient lines, units, totals), the
  Record payment form (balance, amount chips, GCash reference rule) and the sales
  chart on the dashboard. Everything else stays as server-rendered Blade.
- **Forms still post to Laravel.** A React form is only the screen; saving goes
  through the same controllers and validation as before, and server-side errors
  come back into the form. That is why the test suite didn't need to change.
- **No Node needed to run it.** The compiled files are committed in
  `public/build`, so XAMPP alone is enough. Node is only needed to change the
  design or the React code (`npm install`, `npm run build`), see README.
- Small fixes found while checking every screen against the old build: the
  "Choose file" button has the system's own style, the Search box stays on one
  row on phones, and validation messages for the order form say "customer" and
  "due date and time" instead of "customer id" and "scheduled at".
- 134 tests, all passing on SQLite and on MySQL/MariaDB.

## Update: Ahmed's feedback, round 3 (October 1)

- **The order page has two tabs, Order and Payments**, in the same style as the
  Inventory tabs. Order shows the items, the job order and the details. Payments
  shows the balance and every payment received. The order number, the progress bar
  and the "Next:" line with its button stay on top of both tabs, so the next step is
  never hidden. After saving a payment the page opens on the Payments tab, and
  printing an order still prints everything.
- **No map on the dashboard.** The Owner's dashboard is now what is due, what is low
  and the 14-day sales chart. Each customer and delivery order keeps its own map
  (Show on map) and the Directions for the rider button.
- **Long tables come in pages.** The dashboard's due list shows 5 at a time, a
  customer's orders 10, the Job Orders "waiting" list 5, and the Inventory report's
  stock levels and used tables 10 each. Full lists (orders, customers, payments,
  stock, products, users, report lists) are 15 a page everywhere. If a page runs out
  while someone is on it (for example the last order on page 2 gets handled), the
  last page shows instead of an empty list. Paging a report jumps straight to its
  table, and a printed report says which rows it holds ("Showing 1 to 15 of 40").
  Single records stay whole: an order, a receipt and a job order are printed as one
  sheet, and a count is reviewed and applied as a whole.
- **Even spacing in the sidebar.** Dashboard, Orders, Job Orders, Customers and
  Inventory are one evenly spaced list; only the Owner's pages keep a heading. The
  "Sales" heading went, since Inventory isn't sales.
- 134 tests, all passing on SQLite and on MySQL/MariaDB.

## Update: simpler screens (September 28)

Ahmed tried the system as a first-time user and found too much showing at once.
Nothing was removed; things just stopped showing all at the same time:

- **One main button per screen.** The duplicates are gone: the dashboard's second
  "New order", and the order page's second "Record payment" and "Create job order".
  The button next to "Next:" is always the one to press.
- **Shorter menu.** Stock Levels, Stock In, Adjustments and Physical Counts are now
  tabs on one **Inventory** page, and the Payments list is a tab inside **Orders**.
  The Owner sees 8 links instead of 12; Staff see 5.
- **Simpler dashboard.** Staff see what is due today and tomorrow and what is low.
  The Owner also gets the 14-day sales chart and the deliveries map (the map was
  removed again on October 1). The stat tiles
  and the "open orders by stage" card are gone; their numbers live on the Orders
  and Job Orders tabs.
- **Less on the order page.** Print and Cancel order sit under a small **More**
  button, and the map in Details opens with **Show on map**.
- **Hints for first-timers.** Every empty page now says what to press first, for
  example "No orders here. Start with the \"New order\" button above."

## Update: new look and open map (September 28)

- **New look for the whole system:** cream background, dark sidebar with the
  active page highlighted, rounded cards, stat tiles with icons, clearer tables
  and forms, softer pop-ups, and a new login page. Same layout and features.
- **Dashboard:** a bar chart of sales booked in the last 14 days and a list of
  open orders by stage, next to what is due and what is low.
- **Map:** no longer limited to Davao. It still opens on Davao City and search
  shows Davao places first, but a pin can be anywhere in the Philippines.

## Update: Ahmed's feedback (September 27)

- **Deactivate is red** on the Customers and Users lists and the customer page
  (Activate is green).
- **Addresses are split into parts** instead of one box: house no. and street,
  subdivision/building/landmark, barangay, city or municipality, province and ZIP
  code. This is used for the customer's saved address and for an order's delivery
  address.
- **Map (Leaflet + OpenStreetMap), Davao by default:**
  - Click the map or drag the pin, and the address fields fill in by themselves
    (street, barangay, city, province, ZIP) from OpenStreetMap's real address data.
  - Search a place ("SM Lanang", "Toril public market") or press **My location**
    to use the phone's or laptop's location.
  - The pin is saved with the address (latitude and longitude).
  - Customer and order pages show the pin on a map, with a **Directions** link
    that opens Google Maps for the rider.
  - The dashboard has a **Deliveries in the next 7 days** map with every delivery pinned
    (removed again on October 1; the map stays on each order and customer).
- **Delivery address:** a new delivery order starts with the customer's saved
  address, which can be changed for that order (for example, a party venue). The
  order keeps its own copy, so it doesn't change if the customer moves later.
- The map needs internet. Without it, everything else still works; the address is
  just typed in by hand.

## 1. Interface

- One shared stylesheet (`public/css/app.css`) and one small script
  (`public/js/app.js`) for every page. Before, 26 of the 31 pages repeated their
  own CSS and there were 541 inline styles; now there are none.
- Sidebar menu grouped by who uses it (simplified again since, see the updates above).
  Staff only see what they can use.
- Every edit form is a pop-up (modal): customers, products, ingredients, order
  details, item details, payments, job orders, cancelling an order, fixing an
  ingredient-used line, resetting a password. If a save fails, the pop-up opens
  again with the error next to the field.
- Big documents with many lines stay full pages: New order, Stock in, New
  adjustment and Count stock. Lines are added and removed with "Add another" buttons.
- Less on each screen: the dashboard shows what is due today and tomorrow and
  what is low, and each order page says what to do next ("Next: collect the down
  payment of ₱830.00").
- Risky actions ask first (deactivate, cancel, mark as done, apply a count).
- Success messages fade after a few seconds; error messages stay until closed.
- Works on phones: the menu slides in, and tables turn into stacked rows.
- Orders, job orders, stock in and reports can be printed.
- Double-clicking a save button can't save twice.

## 2. Roles and permissions

Only Owner and Staff exist (the Baker and Delivery roles had no screens and were
removed). The Owner-only actions are protected on the server, not just hidden.

| | Owner | Staff |
|---|:---:|:---:|
| Customers, orders, payments, job orders | ✓ | ✓ |
| Log ingredients used on a job order (stock out) | ✓ | ✓ |
| View stock levels and history, count stock | ✓ | ✓ |
| Stock in, adjustments, apply count corrections | ✓ | |
| Fix or remove a wrong ingredient-used line | ✓ | |
| Products, ingredients, reports, users | ✓ | |

## 3. Use case changes

- **Manage Orders** replaces the separate create, view and update orders. An order
  connects to its payments, its job order, and the ingredients deducted for it.
- **Order steps:** Awaiting down payment, then Confirmed (automatic when 50% is
  paid), In production (when the job order starts), Ready for release (when the job
  order is done), Out for delivery (deliveries only), then Completed (only when fully
  paid). An order can be cancelled with a reason before it leaves the shop.
- **Stock in is one to many:** one receipt (receipt number, supplier, date) holds
  many ingredient lines, each with quantity, cost and expiry.
- **Stock out** is the list of ingredients used on a job order. Staff log it; it
  can't use more than what is in stock.
- **Stock adjustment** covers spoilage, expired, damaged, entry corrections and
  count corrections, each with a written reason. Spoilage, expired and damaged can
  only remove stock.
- **Physical count** is blind: Staff type what is on the shelf without seeing the
  system number. Only the newest count can be applied, and only by the Owner.
- **Reports:** Sales, Collections, Job Orders and Inventory.
- **Customers and users** have Deactivate and Activate instead of Delete.

## 4. Database (normalized)

Nothing that can be calculated is stored, so totals can never disagree with
their lines:

- Order total = sum of item quantity × price. Paid = sum of payments.
  Balance, down payment (50%) and payment status are worked out from those.
- Stock on hand = stock in − stock out + adjustments.
- Count difference = counted − system amount at the time of the count.

Tables (foreign keys in brackets):

| Table | Holds |
|---|---|
| users | Owner and Staff accounts (role, is_active) |
| customers | name, contact number (unique, digits only), address parts (street, address line 2, barangay, city, province, postal code, latitude, longitude), Facebook name, is_active |
| products | category (cake, pastry, souvenir), name, base price, is_active |
| orders | order number, date, due date and time, pickup or delivery, delivery address parts (same fields as the customer's, prefixed delivery_; empty for pickups), status, cancel reason, notes [customer, created by] |
| order_items | flavor, size, design, message, reference photo, quantity, agreed unit price [order, product] |
| payments | type, method, amount, reference number, date paid [order, received by] |
| job_orders | production date, status, started and finished times, notes [order (one per order), assigned to] |
| ingredients | name, unit, reorder level, is_active |
| stock_ins + stock_in_items | receipt header + one line per ingredient [received by; ingredient] |
| stock_outs + stock_out_items | ingredients used on a job order [job order, recorded by; ingredient] |
| stock_adjustments + stock_adjustment_items | reason and remarks + signed quantities [adjusted by, physical count; ingredient] |
| physical_counts + physical_count_items | date and shift + counted and system amounts [counted by; ingredient] |

The migrations are the only source for the tables. `database/sweetnest_oims.sql`
is generated from them, so the two can't differ, and it now includes the
`sessions` table that logins need.

## 5. Bugs and issues fixed

From the review (IDs match the review document):

- **B1** Order numbers come from the order's ID (SN-2026-0001), so they never repeat.
- **B2** Search no longer cancels the status, type, method or low-stock filters.
- **B3** Report and list totals cover the whole period, not just the page on screen.
- **B4** Time zone is Asia/Manila for the app and for the database connection.
- **B5** Stock can't go below zero (ingredients used, adjustments, corrections).
- **B6** The SQL file includes the `sessions` table.
- **B7** Orders are cancelled, never deleted.
- **B8** One job order per order.
- **B9** Cancelling a job order puts the order back to Confirmed; cancelling an order
  cancels its unfinished job order.
- **B10** Status rules are enforced: only the allowed next steps are offered or accepted.
- **B11** No payments on cancelled orders.
- **B12** Collections are split into down payment, balance payment and full payment.
- **B13** Flavor, size, design and message each show whenever they are filled in.
- **B14, R8** Baker and Delivery removed everywhere.
- **B15** New test suite: 134 tests as of October 1, all passing on MySQL and SQLite.
- **B16** A deactivated user is signed out on their next click.
- **B17** Contact numbers are saved as digits only and must be unique, so
  "0917-123-4567" and "+63 917 123 4567" are recognized as the same number.
- **B18** Orders can't be due in the past.
- **B19** Removing a wrong ingredient-used line deletes that line (stock comes
  back) instead of recording a fake adjustment. Owner only.
- **B20** Login locks for a minute after 5 wrong tries.
- **B21** "Remember me" removed; sessions end after 2 hours without activity.
- **F1** A delivery with no address typed uses the customer's saved address.
- **F2** Customer search is grouped the same safe way as the other lists.
- **F3** Open orders are sorted by due date, nearest first.
- **F4** No stored totals left to drift.
- **F5** Order numbers use the Philippine year.
- **F6** The customer list on New order has a "type to find" box once there are
  more than 8 customers.
- **F7** Payments lock the order while saving, and buttons can't be double-clicked.
- **F8** Photos show without `storage:link`, and a replaced photo is deleted.
- **D1** Count boxes start empty and the system number is hidden (blind count).
- **D2** The job order is what moves an order into In production and Ready.
- **D3** Error messages stay until closed.
- **D4** Orders have a Cancelled tab (and tabs for every status).
- **R1** One stylesheet, no repeated CSS, no inline styles.
- **R2** SQL file generated from the migrations.
- **R3** Removed unused code: welcome page, the Vite and Tailwind setup, old helpers.
- **R4** Routes grouped once by role.
- **R5** Status changes happen in one place (the order page).
- **R6** Dashboard simplified; all queries moved out of the views.
- **R7** Order tab counts come from one query.
- **R9** Reference photos can be attached, shown and replaced.
- **R10** Passwords are hashed in one place (the User model).

Found and fixed during the final check:

- Two unapplied counts showing the same shortage could be applied twice; now only
  the newest count can be applied and counts are always for today.
- After an error, the "Create job order" pop-up on the Job Orders page could post to
  a different order.
- A balance under ₱1 couldn't be paid; the minimum payment is now ₱0.01.
- An order worth ₱0 got stuck; orders must be worth more than ₱0.
- A payment or job order error was lost if someone else changed the order at the
  same moment; it now shows at the top of the page.
- The Owner couldn't correct a line to exactly the amount available (rounding).
- A delivery that is already out can't be switched to pickup.
- Starting, finishing or cancelling now re-checks the latest status first.
- A typed new password is never kept after a form error.
- Odd values typed into the address bar are ignored instead of showing an error page.
- The edit-item pop-up no longer carries a photo chosen for another item.
- Money paid before reaching 50% is labelled down payment.
- Photo size is checked before uploading, using the server's real limit.

## 6. Notes for the panel

- **Blind count:** the count form hides the system number so Staff count the shelf
  instead of copying the screen. Staff can still see stock levels elsewhere,
  because they need them when logging ingredients used.
- **Items after saving:** product, quantity and price are fixed once an order is
  saved, because the 50% down payment is based on them. Flavor, size, design,
  message and photo can be edited until baking starts.
- **Refunds** for cancelled orders are handled outside the system; payments already
  received stay on record.
- **Photo size** depends on PHP's `upload_max_filesize` (XAMPP allows 40 MB, plain
  PHP 2 MB). The form shows the real limit.

## 7. For the documentation (to update later)

The paper's ERD, use case diagram, data dictionary and DFD still describe the first
version. The table list in section 4 and the use case changes in section 3 are what
they should match. From the latest update: the customers and orders tables now have
the address parts (street, address line 2, barangay, city, province, postal code,
latitude, longitude), and "Pin the address on the map" is part of managing
customers and orders.
