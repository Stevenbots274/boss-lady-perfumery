# Boss Lady Perfumery — WhatsApp Commerce Store

## Customer flow
Shop → Add to cart → Enter delivery details → Get Order ID → Continue to WhatsApp → Confirm payment & delivery on WhatsApp.

## Included
- Product catalogue with names, descriptions, prices, images and stock
- Cart
- Website order creation
- Unique order IDs
- Order tracking page
- WhatsApp checkout
- WhatsApp ordering directly from every product
- Admin page for adding/hiding products and updating order status
- Supabase PostgreSQL database

## Payment
There is **no Paystack integration** in this version. Payment is handled manually through WhatsApp after an order is created.

The storefront does not load the Paystack SDK or claim to accept online card payments.

## Setup
1. Create a Supabase project and open its SQL Editor.
2. Run `schema.sql` in the SQL Editor.
3. Set the server environment variables listed below.
4. Deploy the files to Vercel with the included PHP runtime, or use PHP 8.1+ hosting with PDO PostgreSQL enabled.
5. Add the real perfumes, prices and product images in the admin panel.
6. Enable HTTPS. The included `.htaccess` redirects HTTP and blocks direct access to configuration and database files.

For an existing installation, run `migration-security.sql` before deploying the updated PHP files. Configure the Apache virtual host with the real canonical `ServerName`; non-Apache servers must add equivalent HTTPS, HSTS, dot-file, and source-file blocking rules.

### Vercel
The included `vercel.json` uses the `vercel-php` community runtime and routes the application through `api/index.php`. The app uses Supabase PostgreSQL for products, orders, and rate limits. Add the `BL_*` environment variables under Vercel → Project Settings → Environment Variables. Vercel's filesystem is ephemeral, so do not use it for database storage.

Supabase provides the PostgreSQL database and Auth used by this version.

### Environment variables
- `BL_DB_DSN` — `pgsql:host=db.project-ref.supabase.co;port=5432;dbname=postgres;sslmode=require`
- `BL_DB_USER` and `BL_DB_PASSWORD` — the Supabase database account and password
- `BL_SITE_URL` — the HTTPS site URL, without a trailing slash
- `BL_WHATSAPP` — WhatsApp number with country code and no punctuation
- `BL_SUPABASE_URL` — your Supabase project URL, such as `https://project-id.supabase.co`
- `BL_SUPABASE_ANON_KEY` — Supabase publishable/anon key; this may be sent to the browser
- `BL_ADMIN_EMAIL` — the one Supabase Auth email allowed to access `/admin.php`

An empty stock field means unlimited stock. A numeric stock value is reserved atomically when an order is created and released once if the order is cancelled.

The WhatsApp number currently configured is 2349067956221.

## Order pages
Each order has a private URL containing a high-entropy access token, like:
`https://YOUR-DOMAIN.com/order/64-character-token`

No customer login is required. Anyone who has the complete private link can view the order page. The order link is automatically included in the WhatsApp message sent to Boss Lady. The order ID and checkout phone number are required for status tracking.

## Admin security
- `/admin.php` requires the authorized Supabase Auth email and password.
- The Supabase access token is stored in a Secure, HttpOnly, SameSite cookie and checked on every request.
- The browser sends passwords only to the configured Supabase project, never to this PHP app.
- All admin state-changing actions use POST and a CSRF token.
- Do not share admin credentials.

### Supabase Auth setup
1. Create a Supabase project and enable the Email provider under Authentication → Providers.
2. Create the Boss Lady admin user under Authentication → Users.
3. Disable public sign-ups after creating that user.
4. Add `BL_SUPABASE_URL`, `BL_SUPABASE_ANON_KEY`, and `BL_ADMIN_EMAIL` to Vercel.

The PHP server verifies the Supabase access token through Supabase's `/auth/v1/user` endpoint. Never put a Supabase service-role key in the browser, repository, or Vercel client-side variables.
