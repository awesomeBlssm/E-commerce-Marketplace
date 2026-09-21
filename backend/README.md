# E-commerce Marketplace Backend

Laravel API backend for the e-commerce marketplace. The backend owns authentication, customer data, catalog management, carts, checkout order snapshots, inventory, fulfillment, returns, payments, shipping, tax, and promotions.

## Stack

- Laravel 12
- PHP 8.2+
- MySQL
- Eloquent ORM
- UUID primary keys for domain entities
- JSON API under `/api`

## Project Structure

```text
app/
	Http/Controllers/       API controllers grouped by domain
	Http/Middleware/        API token, role, and ownership middleware
	Models/                 Eloquent models grouped by domain
database/
	factories/              Test and development data factories
	migrations/             MySQL schema migrations
	seeders/                Admin and catalog seed data
routes/
	api.php                 API route loader
	api/                    Domain route definitions
tests/                    Feature and unit tests
```

## Local Setup

From the `backend` directory:

```powershell
composer install
Copy-Item .env.example .env
composer dump-autoload
php artisan key:generate
```

Configure MySQL in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e-commerce-marketplace
DB_USERNAME=root
DB_PASSWORD=
```

Run the schema and seed data:

```powershell
php artisan migrate
php artisan db:seed
```

For a disposable local database:

```powershell
php artisan migrate:fresh --seed
```

`migrate:fresh` deletes all database data. Do not use it on a shared or important database.

Start the API:

```powershell
php artisan serve
```

The default base URL is `http://127.0.0.1:8000`.

## Seed Accounts

The default seeders create an administrator and a demo seller:

| Type | Email | Password |
| --- | --- | --- |
| Admin | `admin@ecomarket.com` | `password123` |
| Seller | `seller@ecomarket.com` | `password123` |

Seeders are repeatable. `CatalogSeeder` creates demo brands, categories, products, options, variants, and images using stable identifiers.

## Authentication

Registration and login set an opaque `HttpOnly` cookie. Only the SHA-256 token hash is stored in `user_sessions`; the token is not returned to JavaScript.

```http
Accept: application/json
Content-Type: application/json
Cookie: auth_token=...
```

The frontend must send API requests with credentials enabled. In production, set
`SESSION_SECURE_COOKIE=true` and serve the frontend and API over HTTPS. The
default `SameSite=Lax` setting should be changed to `none` only when the
frontend and API are on different sites, and then `SESSION_SECURE_COOKIE` must
also be `true`.

### Authentication endpoints

| Method | Endpoint | Access | Purpose |
| --- | --- | --- | --- |
| POST | `/api/auth/register` | Public | Create a user and linked customer profile |
| POST | `/api/auth/login` | Public | Authenticate and issue a bearer token |
| GET | `/api/auth/me` | Authenticated | Return the current user and customer |
| POST | `/api/auth/logout` | Authenticated | Revoke the current bearer token |

Example login request:

```json
{
	"email": "admin@ecomarket.com",
	"password": "password123"
}
```

User types are:

- `user`: shopper permissions
- `seller`: shopper permissions plus ownership-based catalog management
- `admin`: administrative access and ownership bypass where configured

## API Routing

All API routes are loaded from [routes/api.php](routes/api.php). The route files are separated by domain.

### Public catalog

| Methods | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/brands`, `/api/brands/{brand}` | Browse brands |
| GET | `/api/categories`, `/api/categories/{category}` | Browse categories |
| GET | `/api/products`, `/api/products/{product}` | Browse products |
| GET | `/api/products/{product}/categories` | Read product categories |
| GET | `/api/products/{product}/options` | Read product options |
| GET | `/api/products/{product}/options/{option}` | Read one option |
| GET | `/api/products/{product}/options/{option}/values` | Read option values |
| GET | `/api/products/{product}/variants` | Read variants |
| GET | `/api/products/{product}/variants/{variant}` | Read one variant |
| GET | `/api/products/{product}/images` | Read product images |
| GET | `/api/products/{product}/images/{image}` | Read one image |
| GET | `/api/products/{product}/reviews` | Read published reviews |

### Catalog management

Catalog writes require `auth.api`. Brand and category management is admin-only. Product, option, variant, and image writes require `seller` or `admin`; sellers may only modify products where `products.seller_id` equals their user ID.

| Methods | Endpoint | Access |
| --- | --- | --- |
| POST, PUT, PATCH, DELETE | `/api/brands` and `/api/brands/{brand}` | Admin |
| POST, PUT, PATCH, DELETE | `/api/categories` and `/api/categories/{category}` | Admin |
| POST | `/api/products` | Seller or admin |
| PUT, PATCH, DELETE | `/api/products/{product}` | Owning seller or admin |
| POST, DELETE | `/api/products/{product}/categories/{category}` | Owning seller or admin |
| POST, PUT, PATCH, DELETE | `/api/products/{product}/options...` | Owning seller or admin |
| POST, PUT, PATCH, DELETE | `/api/products/{product}/variants...` | Owning seller or admin |
| POST, PUT, PATCH, DELETE | `/api/products/{product}/images...` | Owning seller or admin |

### Cart

Cart routes require authentication. A cart belongs to the authenticated customer; clients cannot choose another `customer_id`.

| Methods | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/carts` | List the current customer's carts |
| POST | `/api/carts` | Create an active cart |
| GET, PUT, PATCH, DELETE | `/api/carts/{cart}` | Read or manage an owned cart |
| GET | `/api/carts/{cart}/items` | List cart items |
| POST | `/api/carts/{cart}/items` | Add a variant or increase its quantity |
| GET, PUT, PATCH, DELETE | `/api/carts/{cart}/items/{item}` | Read, update, or remove an item |

Cart items contain a variant and quantity. Prices remain on `product_variants` and are read live; the cart is not the trusted source for checkout totals.

### Shopper resources

These routes require authentication and customer ownership unless marked admin-only.

| Methods | Endpoint | Access |
| --- | --- | --- |
| GET | `/api/customers` | Admin |
| GET, PUT, PATCH, DELETE | `/api/customers/{customer}` | Customer owner or admin |
| GET, POST, PUT, PATCH, DELETE | `/api/customers/{customer}/addresses...` | Customer owner or admin |
| GET, POST, PUT, PATCH, DELETE | `/api/customers/{customer}/wishlists...` | Customer owner or admin |
| GET, POST, DELETE | `/api/wishlists/{wishlist}/items...` | Wishlist owner or admin |
| POST | `/api/products/{product}/reviews` | Authenticated customer |
| GET, PUT, PATCH, DELETE | `/api/reviews/{review}` | Review owner or admin |

### Orders and checkout

| Methods | Endpoint | Access |
| --- | --- | --- |
| GET | `/api/orders` | Authenticated; customer-scoped |
| POST | `/api/orders` | Authenticated customer |
| GET | `/api/orders/{order}` | Order owner or admin |
| GET, POST | `/api/orders/{order}/lines` | Order owner or admin |
| GET, POST | `/api/orders/{order}/addresses` | Order owner or admin |

Order creation derives the customer, order number, product title, SKU, variant title, unit prices, totals, currency, status, and timestamps on the server. The frontend must not submit trusted totals.

Current checkout limitations:

- Shipping is currently zero.
- Tax is currently zero.
- Discounts are currently zero.
- Inventory reservation is not yet part of order creation.
- Payment processor capture is not yet part of order creation.

### Payments and payment methods

| Methods | Endpoint | Access |
| --- | --- | --- |
| GET, POST, PUT, PATCH, DELETE | `/api/customers/{customer}/payment-methods...` | Customer owner or admin |
| GET, POST, PUT, PATCH | `/api/orders/{order}/payments...` | Order owner or admin |

Only processor references and safe display fields should be stored. Raw card data must never be sent to or stored by this API.

### Inventory and fulfillment

All inventory and fulfillment routes are admin-only.

| Methods | Endpoint | Purpose |
| --- | --- | --- |
| GET, POST, PUT, PATCH, DELETE | `/api/warehouses...` | Manage warehouses |
| GET, POST, PUT, PATCH, DELETE | `/api/inventory-items...` | Manage stock balances |
| GET, POST | `/api/inventory-items/{item}/movements` | Read or record inventory movements |
| GET, POST, PUT, PATCH | `/api/orders/{order}/shipments...` | Manage shipments |
| GET, POST | `/api/shipments/{shipment}/lines` | Manage shipment lines |

### Returns and refunds

Order return routes require order ownership. Return line and refund routes require ownership of the parent return.

| Methods | Endpoint | Purpose |
| --- | --- | --- |
| GET, POST, PUT, PATCH | `/api/orders/{order}/returns...` | Request and manage returns |
| GET, POST | `/api/returns/{return}/lines` | Manage returned quantities |
| GET, POST | `/api/returns/{return}/refunds` | Manage refund records |

### Shipping, tax, and promotions

These configuration routes are admin-only.

| Methods | Endpoint | Purpose |
| --- | --- | --- |
| GET, POST, PUT, PATCH, DELETE | `/api/shipping-zones...` | Manage shipping zones |
| GET, POST, PUT, PATCH, DELETE | `/api/shipping-zones/{zone}/rates...` | Manage shipping rates |
| GET, POST, PUT, PATCH, DELETE | `/api/tax-rates...` | Manage tax rates |
| GET, POST, PUT, PATCH, DELETE | `/api/discounts...` | Manage discounts |
| GET, POST | `/api/discounts/{discount}/redemptions` | Manage redemptions |
| GET, POST, PUT, PATCH, DELETE | `/api/gift-cards...` | Manage gift cards |
| GET, POST | `/api/gift-cards/{card}/transactions` | Manage gift-card ledger entries |

## Middleware

Middleware aliases are registered in [bootstrap/app.php](bootstrap/app.php).

| Alias | Purpose |
| --- | --- |
| `auth.api` | Reads a bearer token, hashes it, checks `user_sessions`, expiration, revocation, and account status |
| `user.type:user,seller,admin` | Allows only selected user types |
| `resource.owner:product` | Allows the owning seller or admin |
| `resource.owner:customer` | Allows the linked customer owner or admin |
| `resource.owner:cart` | Allows the cart's customer owner or admin |
| `resource.owner:order` | Allows the order's customer owner or admin |
| `resource.owner:wishlist` | Allows the wishlist's customer owner or admin |
| `resource.owner:review` | Allows the review's customer owner or admin |
| `resource.owner:returnRequest` | Allows the return's order owner or admin |

## Data Rules

- Money is stored as integer minor units such as cents. Never use floating-point money values.
- Orders snapshot product identity and prices at checkout.
- Order addresses are snapshots and must not be rebuilt from the customer address book.
- Product variants are the sellable and stockable units. Carts and orders reference variants, not products.
- Inventory movements are append-only ledger records.
- Authentication tokens are returned to the client once and stored only as hashes.
- `seller_id` identifies the seller responsible for a product. It is intentionally more specific than a generic `user_id`.

## Response and Validation Conventions

- Successful collection endpoints return Laravel pagination objects with a `data` array.
- Successful creation responses use HTTP `201`.
- Successful deletion responses use HTTP `204`.
- Validation failures use Laravel's normal `422` JSON response.
- Missing or invalid bearer tokens return `401`.
- Authenticated users without the required role or ownership return `403`.
- IDs are UUIDs unless an endpoint explicitly documents otherwise.

## Development Commands

```powershell
# Run tests
php artisan test

# Inspect routes
php artisan route:list --path=api

# Check migration state
php artisan migrate:status

# Seed the admin only
php artisan db:seed --class=AdminUserSeeder

# Seed the demo catalog
php artisan db:seed --class=CatalogSeeder

# Clear cached configuration and routes
php artisan optimize:clear
```

## Testing Expectations

The backend should have feature tests for:

- Registration, login, logout, expired tokens, and revoked tokens.
- Customer, cart, wishlist, review, return, and order ownership.
- Seller product ownership and admin bypass behavior.
- Server-side order pricing and rejection of client-supplied totals.
- Public catalog reads and protected catalog mutations.
- Repeated execution of seeders.

The current scaffold tests are only a smoke check. They do not replace these authorization and checkout tests.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
