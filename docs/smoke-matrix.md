# Smoke Matrix

Use this matrix after each checkpoint.

## Web Flows
1. `GET /login` renders.
2. Admin login succeeds and redirects to dashboard.
3. `GET /{locale}/admin/orders` renders.
4. `GET /{locale}/admin/orders/{order}` renders.
5. `GET /{locale}/admin/invoices/{order}/print` renders.
6. `GET /{locale}/admin/notifications` renders.
7. `POST /{locale}/admin/notifications/send` succeeds for one valid recipient.

## API Flows
1. `POST /api/v1/auth/login` succeeds for active customer.
2. `POST /api/v1/orders` creates order with valid token.
3. `GET /api/v1/orders` returns ownership-scoped data.
4. `GET /api/v1/notifications` returns list.
5. `PATCH /api/v1/notifications/read-all` marks unread notifications.

## Required Result
- All smoke checks pass before tagging checkpoint.

