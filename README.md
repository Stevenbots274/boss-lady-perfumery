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
There is **no Paystack integration** in this version.

Payment is handled manually through WhatsApp after the customer creates an order. This allows Boss Lady to send the correct payment details and confirm payment before processing the order.

## Setup
1. Create a MySQL database and import `schema.sql`.
2. Edit `config.php` with database credentials and the real domain.
3. Change the admin password.
4. Upload the files to PHP hosting.
5. Add the real perfumes, prices and product images in the admin panel.
6. Enable HTTPS.

The WhatsApp number currently configured is 2349067956221.

## Public order pages
Each order has a public URL like:
`https://YOUR-DOMAIN.com/order.php?code=BL-20260823-ABC123`

No customer login is required. Anyone who has the complete link can view the full order page. The order link is automatically included in the WhatsApp message sent to Boss Lady.

## Admin security
- `/admin.php` requires a username and password.
- Admin session cookies are HttpOnly and SameSite.
- Admin login regenerates the session ID.
- Admin POST actions use a CSRF token.
- Store the password as a `password_hash()` value in `config.php`.
- Do not share admin credentials.
