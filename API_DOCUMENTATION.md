# Super3000 API Documentation (v2)

## Base URL
- Development: `http://127.0.0.1:8000/api/v2`
- All responses are JSON.

## Envelope Format
```json
{
  "status": true,
  "data": {},
  "meta": { "message": "..." },
  "errors": []
}
```

## Authentication
- Auth uses Sanctum bearer tokens.
- Customer register endpoint is disabled in v2.
- Protected endpoints require:
  - `Authorization: Bearer <token>`

## Versioning Policy
- `v1` is hard-deprecated and returns `410 Gone`.
- Use only `/api/v2/*`.

## Endpoints

### Auth
- `POST /auth/login`
  - Body: `email`, `password`
  - Returns token + customer profile
- `POST /auth/logout` (auth)
  - Revokes current token
- `GET /me` (auth)
  - Returns authenticated customer profile:
    - `name`, `email`, `phone`, `whatsapp`, `city`, `address`, `is_active`
- `PATCH /me` (auth)
  - Updates customer profile data:
    - `name`, `email`, `phone`, `whatsapp`, `city`, `address`
  - On successful profile change, system sends admin notification that includes:
    - `old_profile`
    - `new_profile`
    - `changed_fields`

### Categories (read-only)
- `GET /categories` (auth)

### Products (read-only)
- `GET /products` (auth)
  - Query:
    - `per_page`, `page`, `q`, `category_id`
    - `filter`:
      - `all` (default)
      - `popular` (الأكثر مبيعًا)
      - `best` (أفضل المنتجات)
      - `new` (جديد)
      - `price` (ترتيب بالسعر)
    - `sort` (اختياري):
      - `newest`
      - `best_selling`
      - `price_asc`
      - `price_desc`
  - Response meta includes:
    - `meta.filters.applied_filter`
    - `meta.filters.applied_sort`
    - `meta.filters.available_filters`
    - `meta.filters.available_sorts`
  - Product object includes:
    - `featured` (true للمنتجات الحديثة)
    - `best_seller` (true للمنتجات المباعة)
    - `sold_qty` (إجمالي الكمية المباعة ضمن حالات الطلب المعتمدة)
- `GET /products/{product}` (auth)

### Orders
- `GET /orders` (auth)
  - Ownership enforced (customer sees only own orders)
- `GET /orders/{order}` (auth)
  - Ownership enforced
- `POST /orders` (auth)
  - Requires header: `Idempotency-Key`
  - Creates order from cart items and snapshots checkout contact fields
  - Accepted contact snapshot fields:
    - `phone`
    - `whatsapp`
    - `email`
    - `city`
    - `address`
    - `notes`
  - Missing contact fields are auto-filled from customer profile defaults (if available)
  - Stock is deducted from default warehouse (`product_stocks` master)

### Notifications
- `GET /notifications` (auth)
  - Includes pagination meta + realtime channel metadata
- `GET /notifications/unread-count` (auth)
- `PATCH /notifications/{notification}/read` (auth)
- `PATCH /notifications/read-all` (auth)

## Realtime Notes
- Realtime delivery uses private channels:
  - Customer: `customer.{id}`
- Broadcast auth endpoint for API clients:
  - `POST /api/broadcasting/auth` (Sanctum + customer token validation)

## Error Codes
- `401` unauthenticated / invalid token
- `403` forbidden
- `404` resource not found
- `405` method not allowed
- `409` stock conflict
- `410` deprecated API version
- `422` validation failed
- `500` server error

## Security Constraints
- Product create/update/delete through customer API is blocked.
- Order ownership is enforced server-side.
- Sensitive endpoints use rate limiting and permission/middleware checks.
