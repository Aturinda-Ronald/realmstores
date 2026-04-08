# Realm Shop

Uganda's leading e-commerce platform built with PHP and MySQL.

## Features

- Product catalog with categories, search, and filtering
- Shopping cart and wishlist
- User accounts and order history
- Order management with status tracking
- Live customer support widget
- Admin panel (products, orders, users, analytics, promotions, marketing)
- Password reset via email (Gmail SMTP)
- Activity logging and analytics
- Mobile responsive design

## Tech Stack

- **Backend:** PHP 8+
- **Database:** MySQL (PDO)
- **Frontend:** HTML, CSS, JavaScript
- **Email:** Gmail SMTP (raw socket, no library)
- **Hosting:** InfinityFree + custom domain

## Local Setup (XAMPP)

1. Clone the repo into `htdocs`:
   ```
   git clone https://github.com/Aturinda-Ronald/realmstores.git realm-shop
   ```
2. Create a MySQL database named `realm_shop` and import your schema.
3. Start Apache and MySQL in XAMPP.
4. Visit `http://localhost/realm-shop`

No config changes needed — the app auto-detects local vs production environment.

## Deployment

Upload files to your hosting root. The app switches automatically:

| Setting | Local | Production |
|---------|-------|------------|
| DB Host | `localhost` | `sql100.infinityfree.com` |
| DB Name | `realm_shop` | InfinityFree DB |
| Site URL | `http://localhost/realm-shop` | `https://realmstores.com` |

## Admin Panel

Access at `/admin` — manage products, orders, users, promotions, and view analytics.

## Environment

The app detects environment via `IS_LOCAL` in `config.php` based on server hostname (`localhost`, `127.0.0.1`, `192.168.*`, `172.*`). All environment-specific values switch automatically — no manual edits needed between local and production.
