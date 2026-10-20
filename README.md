# Laravel Webhook Delivery & Retry

A complete Laravel 11 application for reliable HTTP webhook delivery with database-backed queues, automatic retries, delivery/attempt logging, and a protected dashboard.

> **Isolation note:** This project lives only in `Laravel-Webhook-Retry-Package`. It does **not** change other Laravel 8 (or any other) apps on your machine. Each app keeps its own `vendor/` and `composer.json`.

## Requirements

- PHP 8.2+
- Composer
- MySQL (this project is configured for database `webbook` by default)
- No Redis required (`QUEUE_CONNECTION=database`)

## Setup

```bash
cd C:\xampp\htdocs\Laravel-Webhook-Retry-Package

# Dependencies (already installed if you followed the agent setup)
composer install

# Environment
copy .env.example .env
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

### Migrate & seed

```bash
php artisan migrate
php artisan db:seed
```

The seeder creates a demo endpoint pointing at `https://httpbin.org/post`.

### Run the queue worker (required for delivery)

```bash
php artisan queue:work database --sleep=1 --tries=1
```

Keep this process running while dispatching webhooks.

### Serve the app

With XAMPP Apache:

`http://localhost/Laravel-Webhook-Retry-Package/public/webhooks/dashboard/login`

Or:

```bash
php artisan serve
```

Then open `http://127.0.0.1:8000/webhooks/dashboard/login`.

Dashboard defaults: **admin / secret** (change in `.env`).

## Usage

### 1) Create an endpoint (code / tinker)

```php
use App\Models\WebhookEndpoint;

WebhookEndpoint::create([
    'name' => 'Warehouse API',
    'url' => 'https://warehouse.example.com/api/hooks',
    'secret' => 'shared-hmac-secret',
    'auth_headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
        'X-Api-Key' => 'optional-key',
    ],
    'subscribed_events' => ['order.created', 'inventory.updated'], // null = all events
    'is_active' => true,
    'timeout' => 30,
    'max_attempts' => 5,
    'retry_intervals' => [60, 300, 900, 3600], // seconds between retries
]);
```

### 2) Dispatch an event from your app

```php
use App\Facades\Webhook;

$deliveries = Webhook::dispatch('order.created', [
    'order_id' => 1001,
    'sku' => 'WH-42',
    'qty' => 3,
], eventId: 'order-1001-created'); // stable ID helps prevent duplicates
```

Or via Artisan:

```bash
php artisan webhook:dispatch order.created "{\"order_id\":1001,\"sku\":\"WH-42\"}" --event-id=order-1001-created
```

### 3) What receivers get

JSON POST body = your payload, plus headers:

| Header | Purpose |
|--------|---------|
| `X-Webhook-Event-Id` | Stable event ID (use for idempotency) |
| `X-Webhook-Event` | Event name |
| `X-Webhook-Signature` | HMAC-SHA256 of raw JSON body (when endpoint `secret` is set) |
| Custom `auth_headers` | Whatever you configured |

**Important:** Receivers should treat deliveries as at-least-once and ignore duplicates using `X-Webhook-Event-Id`.

## How retries work

1. First attempt is queued immediately (`DeliverWebhookJob`).
2. On HTTP failure / timeout / connection error, an attempt row is stored.
3. If attempts remain, status becomes `failed` and another job is delayed using `retry_intervals`.
4. When `max_attempts` is exhausted, status becomes `permanently_failed`.
5. From the dashboard you can **Manual retry** a failed delivery (extends `max_attempts` and re-queues).

Duplicate protection on the sender side: unique index on `(webhook_endpoint_id, event_id)`.

## Dashboard

- Login at `/webhooks/dashboard/login`
- Delivery statistics
- Filterable delivery history
- Attempt details (HTTP status, response body, errors, duration)
- Manual retry for failed / permanently failed deliveries

## Testing

```bash
php artisan test
```

Feature coverage includes:

- successful delivery
- failed delivery + delayed retry job
- maximum attempts → permanently failed
- manual retry
- duplicate `event_id` ignored for the same endpoint
- dashboard auth + retry action

Tests use in-memory SQLite (`phpunit.xml`) and do not touch your `webbook` MySQL database.

## Project layout (key files)

```
app/Services/WebhookDispatcher.php      # queue deliveries
app/Services/WebhookDeliveryService.php # HTTP send + attempt logs
app/Jobs/DeliverWebhookJob.php          # retry orchestration
app/Models/WebhookEndpoint.php
app/Models/WebhookDelivery.php
app/Models/WebhookAttempt.php
config/webhooks.php
resources/views/webhooks/dashboard/
```

## Important decisions

- **Laravel 11** matches installed PHP 8.2; Laravel 8 is not a good fit for PHP 8.2.
- **Database queue** so Redis is unnecessary.
- **Application-level retry delays** (not only Laravel job `$tries`) so intervals are explicit and stored on the delivery.
- **Idempotency** uses unique `(endpoint, event_id)` plus documented receiver-side deduplication.

## Composer SSL note (Windows / XAMPP)

If `composer install` fails with `curl error 60` against GitHub, your PHP cURL trust store may not match Windows. This repo may contain project-local `disable-tls` composer config used only for this folder during setup. Prefer fixing `openssl.cafile` / `curl.cainfo` in `php.ini` long-term. This does not affect other projects' Composer configs unless you change them globally yourself.
