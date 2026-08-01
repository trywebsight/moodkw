# Mood Kuwait — One-Product Checkout

Production-ready Laravel 13 checkout website with Tap Payments and Filament v4 admin panel.

## Tech Stack

- Laravel 13, PHP 8.4+
- MySQL
- Blade + Livewire 3
- Tailwind CSS 4
- Filament v4 Admin Panel
- Tap Payments (hosted checkout)

## Features

- **Bilingual (EN / AR)** — Language switcher, RTL support, translated checkout
- **SEO** — Meta tags, Open Graph, Twitter cards (configurable in Admin → Settings)
- **Store logo** — Upload logo in settings (header + favicon)
- **Working hours** — Block orders outside configured hours
- **Notifications** — Email on new orders + sound alert in admin panel
- **Invoices** — PDF download from success page and admin order view
- **Websight branding** — Footer credit with logo on storefront and admin

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env` (MySQL by default):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moodkw
DB_USERNAME=root
DB_PASSWORD=your_password
```

Add Tap API keys (or configure later in Admin → Settings):

```env
TAP_SECRET_KEY=sk_test_xxx
TAP_PUBLIC_KEY=pk_test_xxx
TAP_MODE=test
```

Then:

```bash
npm install && npm run build
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

- **Storefront:** http://localhost:8000
- **Admin:** http://localhost:8000/admin
- **Admin login:** `admin@example.com` / `password`

## Payment Flow

1. Customer submits checkout form
2. Order saved as `pending` payment
3. Tap charge created → redirect to Tap hosted page
4. Customer completes payment
5. Tap webhook verifies hashstring and updates order
6. Customer redirected to success/failure page
7. Success page only shown when payment is verified as paid

## Webhook URL

Configure in Tap dashboard:

```
https://your-domain.com/payment/webhook
```

## Project Structure

- `app/Services/` — Order, Tap Payment, Delivery Fee, Settings
- `app/Livewire/CheckoutPage` — Storefront checkout UI
- `app/Filament/` — Admin resources (Orders, Product, Delivery Fees, Settings)
- `database/seeders/KuwaitLocationSeeder` — Kuwait governorates & areas

## License

MIT
