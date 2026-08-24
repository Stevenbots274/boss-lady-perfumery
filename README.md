# Boss Lady Perfumery — WhatsApp Commerce Store

Live site: https://bossladyperfumery.vercel.app

## Customer flow
Shop → Add to bag → Enter delivery details → Get your order ID → Continue to WhatsApp → Confirm payment and delivery on WhatsApp.

## Included
- Product catalogue with names, descriptions, prices, images and stock
- Cart
- Website order creation
- Unique order IDs
- Order tracking page
- WhatsApp checkout
- WhatsApp support from the catalogue and Scent Finder
- Admin workspace for products, orders, payment status, insights, archive, and settings
- Optional customer accounts with delivered-order testimonial submission and admin moderation
- Public verified testimonial feed with product-specific review links
- Supabase PostgreSQL database

## Payment
There is **no Paystack integration** in this version. Payment is handled manually through WhatsApp after an order is created.

The storefront does not load the Paystack SDK or claim to accept online card payments.

## Setup
1. Create a Supabase project and open its SQL Editor.
2. Run `schema.sql` in the SQL Editor.
3. Run `storage.sql` to enable direct product image uploads from the admin workspace.
4. For an existing database, run `migration-product-lifecycle.sql` to enable permanent product archives.
5. For product videos, run `migration-product-video.sql`.
6. For testimonials, run `migration-testimonials.sql` after the existing `orders`, `order_items`, and `products` tables exist.
7. Create an ImageKit account and use its URL endpoint, public key, and private key for testimonial media and product videos.
8. Set the server environment variables listed below.
9. Deploy the files to Vercel with the included PHP runtime, or use PHP 8.1+ hosting with PDO PostgreSQL enabled.
10. Add the real perfumes, prices and product images in the admin panel.
11. Enable HTTPS. The included `.htaccess` redirects HTTP and blocks direct access to configuration and database files.

For an existing installation, run `migration-security.sql` and `migration-product-lifecycle.sql` before deploying the updated PHP files. Configure the Apache virtual host with the real canonical `ServerName`; non-Apache servers must add equivalent HTTPS, HSTS, dot-file, and source-file blocking rules.

### Vercel
The included `vercel.json` uses the `vercel-php` community runtime and routes the application through `api/index.php`. The app uses Supabase PostgreSQL for products, orders, and rate limits. Add the `BL_*` environment variables under Vercel → Project Settings → Environment Variables. Vercel's filesystem is ephemeral, so do not use it for database storage.

Supabase provides the PostgreSQL database and Auth used by this version. Auth supports both the private admin workspace and the optional customer account page.

### Environment variables
- `BL_DB_DSN` — the exact PostgreSQL DSN for your Supabase project, for example `pgsql:host=aws-1-region.pooler.supabase.com;port=5432;dbname=postgres;sslmode=require`
- `BL_DB_USER` and `BL_DB_PASSWORD` — the Supabase database account and password
- `BL_SITE_URL` — the HTTPS site URL, without a trailing slash
- `BL_WHATSAPP` — WhatsApp number with country code and no punctuation
- `BL_WHATSAPP_DISPLAY` — formatted WhatsApp number shown to customers
- `BL_CALL_DISPLAY` — formatted call number shown to customers
- `BL_SUPABASE_URL` — your Supabase project URL, such as `https://project-id.supabase.co`
- `BL_SUPABASE_ANON_KEY` — Supabase publishable/anon key; this may be sent to the browser
- `BL_ADMIN_EMAIL` — the one Supabase Auth email allowed to access `/admin`; required for admin access
- `BL_IMAGEKIT_URL_ENDPOINT` — your ImageKit HTTPS URL endpoint, such as `https://ik.imagekit.io/your_imagekit_id`
- `BL_IMAGEKIT_PUBLIC_KEY` — ImageKit public key used for direct browser uploads
- `BL_IMAGEKIT_PRIVATE_KEY` — ImageKit private key used only by the PHP server for upload signing and media verification

An empty stock field means unlimited stock. A numeric stock value is reserved atomically when an order is created. When the store is next used after 24 hours without confirmation, a new order is automatically cancelled and its stock is released. An administrator can cancel it sooner.

The WhatsApp number currently configured is 2349067956221.

## Order pages
Each order has a private URL containing a high-entropy access token, like:
`https://YOUR-DOMAIN.com/order/64-character-token`

No customer login is required. Anyone who has the complete private link can view the order page. The order link is automatically included in the WhatsApp message sent to Boss Lady, where the product images can be opened at full size. The order ID and checkout phone number are required for status tracking.

## Customer accounts
Customer accounts are optional. Customers can create an email/password account at `/account` to see orders created with the same email address, including previous guest orders. Guest checkout, WhatsApp confirmation, public order links, and order tracking do not require an account. Email confirmation must be enabled in Supabase Auth before an account can access order history. Customer sessions are stored in Secure, HttpOnly cookies; access tokens are never stored in browser storage.

## Admin security
- `/admin` requires the authorized Supabase Auth email and password.
- The Supabase access token is stored in a Secure, HttpOnly, SameSite cookie and checked on every request.
- The browser sends passwords only to the configured Supabase project, never to this PHP app.
- All admin state-changing actions use POST and a CSRF token.
- The admin Settings section can change the Supabase Auth password after sign-in.
- Do not share admin credentials.

## Testimonials
Customers sign in at `/account`, then can submit one testimonial for each order whose status is `delivered`. Each submission must include at least one clear image or short video; customers may include both. Submissions are stored as `pending` until approved at `/admin/testimonials`. The public `/testimonials` page and homepage show approved stories only; `/testimonials?product=ID` filters them to one product.

Testimonial images, testimonial videos, and product videos upload directly from the browser to ImageKit. The PHP server signs uploads, verifies the ImageKit file details, and stores only verified media URLs and file IDs in PostgreSQL. Product images continue using the existing Supabase Storage flow. Video playback uses lazy metadata loading and ImageKit CDN URLs so source media stays clear without downloading every file up front.

### Supabase Auth setup
1. Create a Supabase project and enable the Email provider under Authentication → Providers.
2. Create the Boss Lady admin user under Authentication → Users.
3. Keep the Email provider enabled. Keep public sign-ups enabled if optional customer accounts are part of the deployment; the admin workspace still only accepts the configured `BL_ADMIN_EMAIL`.
4. Run `migration-security.sql` and `migration-product-lifecycle.sql` for an existing database.
5. Add `BL_SUPABASE_URL`, `BL_SUPABASE_ANON_KEY`, and `BL_ADMIN_EMAIL` to Vercel.

The PHP server verifies the Supabase access token through Supabase's `/auth/v1/user` endpoint. Product uploads use the authenticated user's token and the public `product-images` bucket; the app never exposes a service-role key. Never put a Supabase service-role key in the browser, repository, or Vercel client-side variables. Row-level security is enabled on the application tables so public Supabase roles cannot access store data directly.
