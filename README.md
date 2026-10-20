# Laravel Webhook Delivery & Retry

Reliable outbound webhook delivery for Laravel — database queues, automatic retries, attempt logging, idempotency via event IDs, and a protected monitoring dashboard.

Built with **Laravel 11** and **PHP 8.2**. Redis is **not** required.

---

## Features

- Send JSON events to any HTTP endpoint (warehouse APIs, third-party services, etc.)
- Configurable destination URL, auth headers, event subscriptions, timeout, max attempts, and retry intervals
- Laravel **database** queue driver (no Redis)
- Automatic retries on HTTP / connection failures
- Persists delivery status, event IDs, HTTP responses, errors, and per-attempt logs
- Duplicate dispatch protection with unique `(endpoint, event_id)`
- Manual retry for failed / permanently failed deliveries
- Simple protected Blade dashboard (stats, history, attempt details)
- Feature tests for success, failure, retries, max attempts, and manual retry

---

## Requirements

- PHP 8.2+
- Composer
- MySQL (or MariaDB)
- No Redis

---

## Installation

```bash
git clone https://github.com/YOUR_USERNAME/laravel-webhook-retry.git
cd laravel-webhook-retry

composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

### Configure `.env`

```env
APP_NAME="Webhook Retry"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webbook
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

WEBHOOK_TIMEOUT=30
WEBHOOK_MAX_ATTEMPTS=5
WEBHOOK_RETRY_INTERVALS=60,300,900,3600

WEBHOOK_DASHBOARD_USER=admin
WEBHOOK_DASHBOARD_PASSWORD=secret
```

> On older MySQL / MariaDB (XAMPP), keep `DB_COLLATION=utf8mb4_unicode_ci`.

### Migrate & seed

```bash
php artisan migrate
php artisan db:seed
```

Seeder creates a demo endpoint: **Demo Warehouse API** → `https://httpbin.org/post`  
Subscribed events: `order.created`, `inventory.updated`.

---

## Running the app

Use **two terminals**:

**Terminal 1 — web server**

```bash
php artisan serve
```

Dashboard: [http://127.0.0.1:8000/webhooks/dashboard/login](http://127.0.0.1:8000/webhooks/dashboard/login)  
Default login: `admin` / `secret` (change in `.env`).

**Terminal 2 — queue worker (required for delivery)**

```bash
php artisan queue:work database --sleep=1 --tries=1
```

Without the worker, deliveries stay `pending`.

---

## Usage

### Create an endpoint

```php
use App\Models\WebhookEndpoint;

WebhookEndpoint::create([
    'name' => 'Warehouse API',
    'url' => 'https://warehouse.example.com/api/hooks',
    'secret' => 'shared-hmac-secret',
    'auth_headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
    ],
    'subscribed_events' => ['order.created', 'inventory.updated'], // null = all events
    'is_active' => true,
    'timeout' => 30,
    'max_attempts' => 5,
    'retry_intervals' => [60, 300, 900, 3600], // seconds between retries
]);
```

### Dispatch an event (from code)

```php
use App\Facades\Webhook;

Webhook::dispatch('order.created', [
    'order_id' => 1001,
    'sku' => 'WH-42',
    'qty' => 3,
], eventId: 'order-1001');
```

Any event name and JSON payload work — not only orders.  
Use a stable `eventId` so the same event is not queued twice for the same endpoint.

### Dispatch via Artisan

**PowerShell:**

```powershell
php artisan webhook:dispatch order.created '{\"order_id\":1001,\"sku\":\"WH-42\"}' --event-id=order-1001
```

**Bash:**

```bash
php artisan webhook:dispatch order.created '{"order_id":1001,"sku":"WH-42"}' --event-id=order-1001
```

Examples of custom events:

```powershell
php artisan webhook:dispatch inventory.updated '{\"sku\":\"WH-42\",\"qty\":20}' --event-id=inv-42-1
php artisan webhook:dispatch shipment.dispatched '{\"tracking\":\"PK123\"}' --event-id=ship-123
```

> Endpoint must subscribe to that event name (or have `subscribed_events = null`).

---

## What receivers get

| Header | Purpose |
|--------|---------|
| `X-Webhook-Event-Id` | Stable event ID — use for idempotency |
| `X-Webhook-Event` | Event name |
| `X-Webhook-Signature` | HMAC-SHA256 of JSON body (when endpoint `secret` is set) |
| Custom headers | From `auth_headers` on the endpoint |

Body: your JSON payload (`Content-Type: application/json`).

**Receivers should treat delivery as at-least-once** and deduplicate using `X-Webhook-Event-Id`.

---

## How retries work

1. First attempt is queued immediately (`DeliverWebhookJob`).
2. On failure (non-2xx, timeout, connection error), an attempt row is stored.
3. If attempts remain → status `failed`, next job delayed using `retry_intervals`.
4. When `max_attempts` is exhausted → status `permanently_failed`.
5. Dashboard **Manual retry** re-queues the delivery (extends `max_attempts`).

Default intervals: `60s → 300s → 900s → 3600s` (configurable in `.env`).

Sender-side duplicate guard: unique index on `(webhook_endpoint_id, event_id)`.

---

## Dashboard

- Login: `/webhooks/dashboard/login`
- Delivery statistics
- Filterable history
- Attempt details (HTTP status, response body, errors, duration)
- Manual retry for failed deliveries

---

## Testing

```bash
php artisan test
```

Covered scenarios:

- Successful delivery
- Failed delivery + delayed retry job
- Maximum attempts → permanently failed
- Manual retry
- Duplicate `event_id` ignored for the same endpoint
- Dashboard auth + retry action

Tests use in-memory SQLite and do not touch your MySQL database.

---

## Project structure

```
app/
  Console/Commands/DispatchWebhookCommand.php
  Enums/WebhookDeliveryStatus.php
  Facades/Webhook.php
  Http/Controllers/WebhookDashboard/
  Http/Middleware/WebhookDashboardAuth.php
  Jobs/DeliverWebhookJob.php
  Models/WebhookEndpoint.php
  Models/WebhookDelivery.php
  Models/WebhookAttempt.php
  Services/WebhookDispatcher.php
  Services/WebhookDeliveryService.php
config/webhooks.php
database/migrations/
database/seeders/WebhookSeeder.php
resources/views/webhooks/dashboard/
tests/Feature/
```

---

## Design notes

- **Database queue** — simpler local/prod setup without Redis
- **App-level retry delays** — intervals are explicit and stored on each delivery
- **Idempotency** — unique `(endpoint, event_id)` plus documented receiver-side dedupe
- **Observability** — every HTTP attempt is logged for debugging and support



## License

MIT
