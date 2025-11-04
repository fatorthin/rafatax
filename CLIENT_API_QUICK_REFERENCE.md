# Quick API Reference - Client

## Base URL

```
http://your-domain.com/api
```

## Authentication Required

All endpoints require Bearer token:

```
Authorization: Bearer {your-token}
Accept: application/json
```

## Quick Examples

### 1. Get All Clients

```bash
GET /api/clients?per_page=10&search=PT
```

### 2. Get Single Client

```bash
GET /api/clients/1
```

### 3. Create Client

```bash
POST /api/clients
Content-Type: application/json

{
  "code": "CLT001",
  "company_name": "PT Example"
}
```

### 4. Update Client

```bash
PUT /api/clients/1
Content-Type: application/json

{
  "company_name": "PT Updated Name",
  "status": "active"
}
```

### 5. Delete Client (Soft)

```bash
DELETE /api/clients/1
```

### 6. Restore Client

```bash
POST /api/clients/1/restore
```

### 7. Force Delete

```bash
DELETE /api/clients/1/force
```

## Query Parameters (GET /api/clients)

| Parameter      | Type   | Description        | Example                |
| -------------- | ------ | ------------------ | ---------------------- |
| per_page       | int    | Items per page     | `per_page=20`          |
| search         | string | Search term        | `search=PT`            |
| status         | string | Filter by status   | `status=active`        |
| type           | string | Filter by type     | `type=PT`              |
| grade          | string | Filter by grade    | `grade=A`              |
| jenis_wp       | string | Filter by jenis WP | `jenis_wp=Badan`       |
| sort_by        | string | Sort field         | `sort_by=company_name` |
| sort_direction | string | Sort direction     | `sort_direction=asc`   |

## Required Fields (Create)

-   ✅ `code` (unique)
-   ✅ `company_name`

## All Available Fields

```json
{
    "code": "string (required, unique)",
    "company_name": "string (required)",
    "phone": "string (optional)",
    "address": "string (optional)",
    "owner_name": "string (optional)",
    "owner_role": "string (optional)",
    "contact_person": "string (optional)",
    "npwp": "string (optional)",
    "jenis_wp": "string (optional)",
    "grade": "string (optional)",
    "pph_25_reporting": "string (optional)",
    "pph_23_reporting": "string (optional)",
    "pph_21_reporting": "string (optional)",
    "pph_4_reporting": "string (optional)",
    "ppn_reporting": "string (optional)",
    "spt_reporting": "string (optional)",
    "status": "string (optional)",
    "type": "string (optional)"
}
```

## Response Codes

-   `200` OK - Success
-   `201` Created - Resource created
-   `400` Bad Request - Invalid request
-   `401` Unauthorized - Missing/invalid token
-   `404` Not Found - Resource not found
-   `422` Validation Error - Invalid input
-   `500` Server Error - Internal error

## cURL Examples

### Login & Get Token

```bash
curl -X POST http://your-domain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

### List Clients

```bash
curl -X GET "http://your-domain.com/api/clients?per_page=10" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Create Client

```bash
curl -X POST http://your-domain.com/api/clients \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "code": "CLT001",
    "company_name": "PT Test Client",
    "phone": "081234567890",
    "status": "active"
  }'
```

### Update Client

```bash
curl -X PUT http://your-domain.com/api/clients/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "company_name": "PT Updated Name"
  }'
```

### Delete Client

```bash
curl -X DELETE http://your-domain.com/api/clients/1 \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

## Postman Collection

Import these settings in Postman:

1. **Authorization Tab:**

    - Type: Bearer Token
    - Token: {your-token}

2. **Headers Tab:**

    - Accept: application/json
    - Content-Type: application/json (for POST/PUT)

3. **Environment Variables:**
    - base_url: http://your-domain.com/api
    - token: {your-token}

Then use: `{{base_url}}/clients`

---

For complete documentation, see: `CLIENT_API_DOCUMENTATION.md`
