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
- MySQL database

## Payment
There is **no Paystack integration** in this version. Payment is handled manually through WhatsApp after an order is created.

The storefront does not load the Paystack SDK or claim to accept online card payments.

## Setup
1. Create a MySQL database and import `schema.sql`.
2. Set the server environment variables listed below.
3. Upload the files to PHP 8.1+ hosting with PDO MySQL enabled and MySQL 8.0.16+ (or MariaDB with enforced checks).
4. Add the real perfumes, prices and product images in the admin panel.
5. Enable HTTPS. The included `.htaccess` redirects HTTP and blocks direct access to configuration and database files.

For an existing installation, run `migration-security.sql` before deploying the updated PHP files. Configure the Apache virtual host with the real canonical `ServerName`; non-Apache servers must add equivalent HTTPS, HSTS, dot-file, and source-file blocking rules.

### Environment variables
- `BL_DB_DSN` — for example `mysql:host=localhost;dbname=boss_lady;charset=utf8mb4`
- `BL_DB_USER` and `BL_DB_PASSWORD` — a least-privilege MySQL account
- `BL_SITE_URL` — the HTTPS site URL, without a trailing slash
- `BL_WHATSAPP` — WhatsApp number with country code and no punctuation
- `BL_ADMIN_USER` — admin username
- `BL_ADMIN_PASSWORD_HASH` — output of `password_hash('your-password', PASSWORD_DEFAULT)`; never use the plain password

An empty stock field means unlimited stock. A numeric stock value is reserved atomically when an order is created and released once if the order is cancelled.

The WhatsApp number currently configured is 2349067956221.

## Order pages
Each order has a private URL containing a high-entropy access token, like:
`https://YOUR-DOMAIN.com/order/64-character-token`

No customer login is required. Anyone who has the complete private link can view the order page. The order link is automatically included in the WhatsApp message sent to Boss Lady. The order ID and checkout phone number are required for status tracking.

## Admin security
- `/admin.php` requires a username and password.
- Admin session cookies are Secure, HttpOnly, and SameSite.
- Admin sessions expire after 30 minutes of inactivity or 8 hours.
- Admin login regenerates the session ID and throttles repeated failures within a session.
- All admin state-changing actions use POST and a CSRF token.
- Store the password as a `password_hash()` value in `BL_ADMIN_PASSWORD_HASH`.
- Do not share admin credentials.
