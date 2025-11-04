# Client API Documentation

## Overview

RESTful API untuk mengelola data Client (Klien) dengan autentikasi menggunakan Laravel Sanctum.

## Base URL

```
http://your-domain.com/api
```

## Authentication

Semua endpoint memerlukan autentikasi menggunakan Bearer Token (Laravel Sanctum).

**Header:**

```
Authorization: Bearer {your-token}
Accept: application/json
```

## Endpoints

### 1. Get All Clients (List)

Mendapatkan daftar semua klien dengan pagination.

**Endpoint:** `GET /api/clients`

**Query Parameters:**

-   `per_page` (optional) - Jumlah data per halaman. Default: 15
-   `search` (optional) - Pencarian berdasarkan company_name, code, owner_name, phone, atau npwp
-   `status` (optional) - Filter berdasarkan status
-   `type` (optional) - Filter berdasarkan type
-   `grade` (optional) - Filter berdasarkan grade
-   `jenis_wp` (optional) - Filter berdasarkan jenis WP
-   `sort_by` (optional) - Field untuk sorting. Default: created_at
-   `sort_direction` (optional) - Arah sorting (asc/desc). Default: desc

**Example Request:**

```bash
curl -X GET "http://your-domain.com/api/clients?per_page=10&search=PT&status=active" \
  -H "Authorization: Bearer {your-token}" \
  -H "Accept: application/json"
```

**Success Response (200 OK):**

```json
{
    "data": [
        {
            "id": 1,
            "code": "CLT001",
            "company_name": "PT Example Indonesia",
            "phone": "081234567890",
            "address": "Jl. Example No. 123",
            "owner_name": "John Doe",
            "owner_role": "Director",
            "contact_person": "Jane Doe",
            "npwp": "12.345.678.9-012.000",
            "jenis_wp": "Badan",
            "grade": "A",
            "pph_25_reporting": "Monthly",
            "pph_23_reporting": "Monthly",
            "pph_21_reporting": "Monthly",
            "pph_4_reporting": "Monthly",
            "ppn_reporting": "Monthly",
            "spt_reporting": "Annual",
            "status": "active",
            "type": "PT",
            "mous": [],
            "staff": [],
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z",
            "deleted_at": null
        }
    ],
    "links": {
        "first": "http://your-domain.com/api/clients?page=1",
        "last": "http://your-domain.com/api/clients?page=10",
        "prev": null,
        "next": "http://your-domain.com/api/clients?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 10,
        "path": "http://your-domain.com/api/clients",
        "per_page": 15,
        "to": 15,
        "total": 150,
        "version": "1.0.0"
    }
}
```

---

### 2. Get Single Client

Mendapatkan detail satu klien beserta relasi (MoUs dan Staff).

**Endpoint:** `GET /api/clients/{id}`

**Example Request:**

```bash
curl -X GET "http://your-domain.com/api/clients/1" \
  -H "Authorization: Bearer {your-token}" \
  -H "Accept: application/json"
```

**Success Response (200 OK):**

```json
{
    "data": {
        "id": 1,
        "code": "CLT001",
        "company_name": "PT Example Indonesia",
        "phone": "081234567890",
        "address": "Jl. Example No. 123",
        "owner_name": "John Doe",
        "owner_role": "Director",
        "contact_person": "Jane Doe",
        "npwp": "12.345.678.9-012.000",
        "jenis_wp": "Badan",
        "grade": "A",
        "pph_25_reporting": "Monthly",
        "pph_23_reporting": "Monthly",
        "pph_21_reporting": "Monthly",
        "pph_4_reporting": "Monthly",
        "ppn_reporting": "Monthly",
        "spt_reporting": "Annual",
        "status": "active",
        "type": "PT",
        "mous": [
            {
                "id": 1,
                "mou_number": "MOU/2024/001",
                "description": "Tax Consulting Services",
                "start_date": "2024-01-01",
                "end_date": "2024-12-31"
            }
        ],
        "staff": [
            {
                "id": 1,
                "name": "Staff Member",
                "email": "staff@example.com",
                "phone": "081234567890"
            }
        ],
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z",
        "deleted_at": null
    },
    "meta": {
        "version": "1.0.0"
    }
}
```

**Error Response (404 Not Found):**

```json
{
    "message": "No query results for model [App\\Models\\Client] 999"
}
```

---

### 3. Create New Client

Membuat klien baru.

**Endpoint:** `POST /api/clients`

**Request Body:**

```json
{
    "code": "CLT002",
    "company_name": "PT New Client",
    "phone": "081234567890",
    "address": "Jl. New Address",
    "owner_name": "Owner Name",
    "owner_role": "CEO",
    "contact_person": "Contact Person",
    "npwp": "12.345.678.9-012.001",
    "jenis_wp": "Badan",
    "grade": "B",
    "pph_25_reporting": "Monthly",
    "pph_23_reporting": "Monthly",
    "pph_21_reporting": "Monthly",
    "pph_4_reporting": "Monthly",
    "ppn_reporting": "Monthly",
    "spt_reporting": "Annual",
    "status": "active",
    "type": "PT"
}
```

**Required Fields:**

-   `code` (string, max:255, unique)
-   `company_name` (string, max:255)

**Optional Fields:**

-   `phone`, `address`, `owner_name`, `owner_role`, `contact_person`, `npwp`, `jenis_wp`, `grade`
-   `pph_25_reporting`, `pph_23_reporting`, `pph_21_reporting`, `pph_4_reporting`, `ppn_reporting`, `spt_reporting`
-   `status`, `type`

**Example Request:**

```bash
curl -X POST "http://your-domain.com/api/clients" \
  -H "Authorization: Bearer {your-token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "CLT002",
    "company_name": "PT New Client",
    "phone": "081234567890",
    "status": "active",
    "type": "PT"
  }'
```

**Success Response (201 Created):**

```json
{
    "data": {
        "id": 2,
        "code": "CLT002",
        "company_name": "PT New Client",
        "phone": "081234567890",
        "status": "active",
        "type": "PT",
        "created_at": "2024-01-02T00:00:00.000000Z",
        "updated_at": "2024-01-02T00:00:00.000000Z"
    },
    "meta": {
        "version": "1.0.0"
    }
}
```

**Validation Error (422 Unprocessable Entity):**

```json
{
    "message": "The code has already been taken.",
    "errors": {
        "code": ["The code has already been taken."]
    }
}
```

---

### 4. Update Client

Update data klien yang sudah ada.

**Endpoint:** `PUT /api/clients/{id}` atau `PATCH /api/clients/{id}`

**Request Body:**

```json
{
    "company_name": "PT Updated Client Name",
    "phone": "081234567899",
    "status": "inactive"
}
```

**Example Request:**

```bash
curl -X PUT "http://your-domain.com/api/clients/1" \
  -H "Authorization: Bearer {your-token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "PT Updated Client Name",
    "status": "inactive"
  }'
```

**Success Response (200 OK):**

```json
{
    "data": {
        "id": 1,
        "code": "CLT001",
        "company_name": "PT Updated Client Name",
        "status": "inactive",
        "updated_at": "2024-01-02T12:00:00.000000Z"
    },
    "meta": {
        "version": "1.0.0"
    }
}
```

---

### 5. Delete Client (Soft Delete)

Soft delete klien (masih bisa di-restore).

**Endpoint:** `DELETE /api/clients/{id}`

**Example Request:**

```bash
curl -X DELETE "http://your-domain.com/api/clients/1" \
  -H "Authorization: Bearer {your-token}" \
  -H "Accept: application/json"
```

**Success Response (200 OK):**

```json
{
    "message": "Client berhasil dihapus"
}
```

---

### 6. Restore Deleted Client

Mengembalikan klien yang sudah di-soft delete.

**Endpoint:** `POST /api/clients/{id}/restore`

**Example Request:**

```bash
curl -X POST "http://your-domain.com/api/clients/1/restore" \
  -H "Authorization: Bearer {your-token}" \
  -H "Accept: application/json"
```

**Success Response (200 OK):**

```json
{
    "data": {
        "id": 1,
        "code": "CLT001",
        "company_name": "PT Example Indonesia",
        "deleted_at": null
    },
    "meta": {
        "version": "1.0.0"
    }
}
```

**Error Response (400 Bad Request):**

```json
{
    "message": "Client tidak dalam keadaan terhapus"
}
```

---

### 7. Force Delete Client

Hapus permanen klien dari database (tidak bisa di-restore).

**Endpoint:** `DELETE /api/clients/{id}/force`

**Example Request:**

```bash
curl -X DELETE "http://your-domain.com/api/clients/1/force" \
  -H "Authorization: Bearer {your-token}" \
  -H "Accept: application/json"
```

**Success Response (200 OK):**

```json
{
    "message": "Client berhasil dihapus permanen"
}
```

---

## Error Responses

### Unauthorized (401)

```json
{
    "message": "Unauthenticated."
}
```

### Forbidden (403)

```json
{
    "message": "This action is unauthorized."
}
```

### Not Found (404)

```json
{
    "message": "No query results for model [App\\Models\\Client] {id}"
}
```

### Validation Error (422)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Error message here"]
    }
}
```

### Server Error (500)

```json
{
    "message": "Server Error",
    "exception": "Exception details..."
}
```

---

## Testing Examples

### Using Postman

1. Set Authorization: Bearer Token
2. Add token dari login endpoint
3. Set Accept header: `application/json`
4. Set Content-Type header: `application/json` (untuk POST/PUT)

### Using cURL

```bash
# Login dulu untuk mendapatkan token
curl -X POST "http://your-domain.com/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'

# Gunakan token yang didapat untuk request lainnya
TOKEN="your-token-here"

# Get all clients
curl -X GET "http://your-domain.com/api/clients" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Create client
curl -X POST "http://your-domain.com/api/clients" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"code": "CLT003", "company_name": "PT Test Client"}'
```

---

## Notes

-   Semua datetime format menggunakan ISO 8601
-   Pagination default: 15 items per page
-   Soft delete aktif - gunakan endpoint restore untuk mengembalikan data
-   Relationships (mous, staff) di-load otomatis pada endpoint show dan setelah create/update
