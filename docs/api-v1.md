## Super3000 API v1

All routes are prefixed with `/api/v1` and return JSON in the following shape:

```json
{
    "data": {
        /* payload */
    },
    "meta": {
        "message": "Human friendly message",
        "pagination": {
            /* optional, for paginated endpoints */
        }
    }
}
```

### Errors (validation)

Validation errors return the same envelope:

```json
{
    "data": null,
    "meta": {
        "message": "Validation error.",
        "errors": {
            "email": ["The email field is required."]
        }
    }
}
```

Authentication uses **Laravel Sanctum** personal access tokens. Send the token as a Bearer token:

```http
Authorization: Bearer {ACCESS_TOKEN}
```

### Auth

- **POST** `/api/v1/auth/login`

    Request body:

    ```json
    {
        "email": "admin@example.com",
        "password": "secret",
        "device_name": "optional-device-name"
    }
    ```

    Response:

    ```json
    {
        "data": {
            "token_type": "Bearer",
            "access_token": "plain-text-token",
            "user": {
                "id": 1,
                "name": "Admin",
                "email": "admin@example.com",
                "roles": ["admin"],
                "permissions": ["products.view", "..."]
            }
        },
        "meta": {
            "message": "Login successful."
        }
    }
    ```

- **POST** `/api/v1/auth/logout` (protected)

    Revokes the current token.

- **GET** `/api/v1/me` (protected)

    Returns the authenticated user profile and permissions.

### Products (protected)

- **GET** `/api/v1/products`

    Query parameters:
    - `q`: search by name or SKU
    - `status`: `active` or `inactive`
    - `page`: page number
    - `per_page`: items per page (default 15)

    Response:

    ```json
    {
        "data": [
            {
                "id": 1,
                "sku": "P-001",
                "name": "Localized name",
                "name_ar": "اسم عربي",
                "name_en": "English name",
                "price": 100,
                "cost": 80,
                "stock": 20,
                "status": "active",
                "category": {
                    "id": 1,
                    "name": "Category name"
                }
            }
        ],
        "links": {
            /* Laravel pagination links */
        },
        "meta": {
            "message": "Products list.",
            "current_page": 1,
            "last_page": 3,
            "total": 30
        }
    }
    ```

- **GET** `/api/v1/products/{id}`

    Returns a single product resource.

### Orders (protected)

- **GET** `/api/v1/orders`

    Query parameters:
    - `status`: `pending|confirmed|shipped|completed|canceled`
    - `payment_status`: `unpaid|partial|paid`
    - `page`, `per_page`

    Response (simplified):

    ```json
    {
        "data": [
            {
                "id": 1,
                "order_no": "ORD-0001",
                "status": "completed",
                "payment_status": "paid",
                "total": 500,
                "cost_total": 400,
                "profit": 100,
                "created_at": "2026-02-05T10:00:00Z",
                "partner": {
                    "id": 1,
                    "name": "Customer name"
                }
            }
        ],
        "links": {
            /* Laravel pagination links */
        },
        "meta": {
            "message": "Orders list.",
            "current_page": 1,
            "last_page": 1,
            "total": 10
        }
    }
    ```

- **GET** `/api/v1/orders/{id}`

    Returns an order with its items:

    ```json
    {
        "data": {
            "id": 1,
            "order_no": "ORD-0001",
            "status": "completed",
            "payment_status": "paid",
            "total": 500,
            "cost_total": 400,
            "profit": 100,
            "created_at": "2026-02-05T10:00:00Z",
            "partner": {
                "id": 1,
                "name": "Customer name"
            },
            "items": [
                {
                    "id": 1,
                    "product_id": 1,
                    "qty": 2,
                    "price": 250,
                    "cost": 200,
                    "line_total": 500,
                    "product": {
                        "id": 1,
                        "name": "Product name",
                        "sku": "P-001"
                    }
                }
            ]
        },
        "meta": {
            "message": "Order details."
        }
    }
    ```
