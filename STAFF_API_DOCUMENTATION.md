# Staff API Documentation

## Overview

API untuk mengelola data Staff dengan dukungan pagination, searching, filtering, dan sorting.

## Base URL

```
http://localhost:8000/api
```

## Endpoints

### 1. Get All Staff (List)

**GET** `/api/staff`

Mendapatkan daftar semua staff dengan pagination.

#### Query Parameters:

-   `per_page` (optional): Jumlah data per halaman (default: 15)
-   `search` (optional): Pencarian berdasarkan name, email, phone, no_ktp
-   `is_active` (optional): Filter berdasarkan status aktif (true/false)
-   `department_id` (optional): Filter berdasarkan department ID
-   `position_id` (optional): Filter berdasarkan position ID
-   `sort_by` (optional): Field untuk sorting (default: created_at)
-   `sort_direction` (optional): Arah sorting (asc/desc, default: desc)
-   `page` (optional): Nomor halaman

#### Example Request:

```bash
# Get all staff
curl -X GET "http://localhost:8000/api/staff"

# With search
curl -X GET "http://localhost:8000/api/staff?search=john"

# With pagination
curl -X GET "http://localhost:8000/api/staff?per_page=10&page=2"

# With filter active staff
curl -X GET "http://localhost:8000/api/staff?is_active=true"

# With sorting
curl -X GET "http://localhost:8000/api/staff?sort_by=name&sort_direction=asc"

# Combined filters
curl -X GET "http://localhost:8000/api/staff?search=john&is_active=true&department_id=1&per_page=20"
```

#### Example Response:

```json
{
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "birth_place": "Jakarta",
            "birth_date": "1990-01-15",
            "address": "Jl. Sudirman No. 123",
            "email": "john@example.com",
            "no_ktp": "3201234567890123",
            "phone": "08123456789",
            "no_spk": "SPK/2024/001",
            "jenjang": "S1",
            "jurusan": "Akuntansi",
            "university": "Universitas Indonesia",
            "no_ijazah": "IJZ/2015/001",
            "tmt_training": "2024-01-01",
            "periode": "2024",
            "selesai_training": "2024-06-01",
            "is_active": true,
            "salary": 5000000,
            "position_status": "Permanent",
            "department": {
                "id": 1,
                "name": "Finance"
            },
            "position": {
                "id": 2,
                "name": "Staff Accountant"
            },
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z",
            "deleted_at": null
        }
    ],
    "links": {
        "first": "http://localhost:8000/api/staff?page=1",
        "last": "http://localhost:8000/api/staff?page=5",
        "prev": null,
        "next": "http://localhost:8000/api/staff?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "path": "http://localhost:8000/api/staff",
        "per_page": 15,
        "to": 15,
        "total": 65,
        "version": "1.0.0"
    }
}
```

---

### 2. Get Single Staff (Detail)

**GET** `/api/staff/{id}`

Mendapatkan detail staff berdasarkan ID, termasuk relasi dengan clients dan trainings.

#### Example Request:

```bash
curl -X GET "http://localhost:8000/api/staff/1"
```

#### Example Response:

```json
{
    "data": {
        "id": 1,
        "name": "John Doe",
        "birth_place": "Jakarta",
        "birth_date": "1990-01-15",
        "address": "Jl. Sudirman No. 123",
        "email": "john@example.com",
        "no_ktp": "3201234567890123",
        "phone": "08123456789",
        "no_spk": "SPK/2024/001",
        "jenjang": "S1",
        "jurusan": "Akuntansi",
        "university": "Universitas Indonesia",
        "no_ijazah": "IJZ/2015/001",
        "tmt_training": "2024-01-01",
        "periode": "2024",
        "selesai_training": "2024-06-01",
        "is_active": true,
        "salary": 5000000,
        "position_status": "Permanent",
        "department": {
            "id": 1,
            "name": "Finance"
        },
        "position": {
            "id": 2,
            "name": "Staff Accountant"
        },
        "clients": [
            {
                "id": 1,
                "name": "PT ABC",
                "pivot": {
                    "staff_id": 1,
                    "client_id": 1
                }
            }
        ],
        "trainings": [
            {
                "id": 1,
                "name": "Tax Training 2024",
                "pivot": {
                    "staff_id": 1,
                    "training_id": 1
                }
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

---

### 3. Create Staff

**POST** `/api/staff`

Membuat data staff baru.

#### Request Headers:

```
Content-Type: application/json
Accept: application/json
```

#### Request Body:

```json
{
    "name": "John Doe",
    "birth_place": "Jakarta",
    "birth_date": "1990-01-15",
    "address": "Jl. Sudirman No. 123",
    "email": "john@example.com",
    "no_ktp": "3201234567890123",
    "phone": "08123456789",
    "no_spk": "SPK/2024/001",
    "jenjang": "S1",
    "jurusan": "Akuntansi",
    "university": "Universitas Indonesia",
    "no_ijazah": "IJZ/2015/001",
    "tmt_training": "2024-01-01",
    "periode": "2024",
    "selesai_training": "2024-06-01",
    "department_reference_id": 1,
    "position_reference_id": 2,
    "is_active": true,
    "salary": 5000000,
    "position_status": "Permanent"
}
```

#### Required Fields:

-   `name` (string, max: 255)

#### Optional Fields:

-   `birth_place` (string, max: 255)
-   `birth_date` (date: YYYY-MM-DD)
-   `address` (string, max: 1000)
-   `email` (email, max: 255, unique)
-   `no_ktp` (string, max: 50, unique)
-   `phone` (string, max: 50)
-   `no_spk` (string, max: 100)
-   `jenjang` (string, max: 100)
-   `jurusan` (string, max: 100)
-   `university` (string, max: 255)
-   `no_ijazah` (string, max: 100)
-   `tmt_training` (date: YYYY-MM-DD)
-   `periode` (string, max: 100)
-   `selesai_training` (date: YYYY-MM-DD)
-   `department_reference_id` (integer, must exist in department_references)
-   `position_reference_id` (integer, must exist in position_references)
-   `is_active` (boolean)
-   `salary` (numeric, min: 0)
-   `position_status` (string, max: 100)

#### Example Request:

```bash
curl -X POST "http://localhost:8000/api/staff" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "phone": "08123456789",
    "is_active": true,
    "department_reference_id": 1,
    "position_reference_id": 2,
    "salary": 6000000
  }'
```

#### Success Response (201 Created):

```json
{
    "data": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "phone": "08123456789",
        "is_active": true,
        "salary": 6000000,
        "department": {
            "id": 1,
            "name": "Finance"
        },
        "position": {
            "id": 2,
            "name": "Staff Accountant"
        },
        "created_at": "2024-11-01T10:30:00.000000Z",
        "updated_at": "2024-11-01T10:30:00.000000Z",
        "deleted_at": null
    },
    "meta": {
        "version": "1.0.0"
    }
}
```

#### Validation Error Response (422 Unprocessable Entity):

```json
{
    "message": "The name field is required. (and 1 more error)",
    "errors": {
        "name": ["Nama staff wajib diisi"],
        "email": ["Email sudah digunakan"]
    }
}
```

---

### 4. Update Staff

**PUT/PATCH** `/api/staff/{id}`

Mengupdate data staff yang sudah ada.

#### Request Headers:

```
Content-Type: application/json
Accept: application/json
```

#### Request Body:

```json
{
    "name": "John Doe Updated",
    "phone": "08198765432",
    "salary": 7000000,
    "is_active": false
}
```

#### Notes:

-   Semua field bersifat optional
-   Hanya field yang dikirim yang akan diupdate
-   Validasi unique (email, no_ktp) akan mengabaikan record yang sedang diupdate

#### Example Request:

```bash
# Update beberapa field
curl -X PUT "http://localhost:8000/api/staff/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "phone": "08198765432",
    "salary": 7000000
  }'

# Atau menggunakan PATCH
curl -X PATCH "http://localhost:8000/api/staff/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "is_active": false
  }'
```

#### Success Response (200 OK):

```json
{
    "data": {
        "id": 1,
        "name": "John Doe",
        "phone": "08198765432",
        "salary": 7000000,
        "is_active": true,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-11-01T11:00:00.000000Z",
        "deleted_at": null
    },
    "meta": {
        "version": "1.0.0"
    }
}
```

---

### 5. Delete Staff

**DELETE** `/api/staff/{id}`

Menghapus staff (soft delete).

#### Example Request:

```bash
curl -X DELETE "http://localhost:8000/api/staff/1"
```

#### Success Response (200 OK):

```json
{
    "message": "Staff berhasil dihapus"
}
```

#### Not Found Response (404):

```json
{
    "message": "No query results for model [App\\Models\\Staff] 999"
}
```

---

## Testing dengan Postman

### Import ke Postman:

1. Buka Postman
2. Click "Import"
3. Pilih "Raw text"
4. Copy dan paste collection JSON di bawah ini

### Postman Collection:

```json
{
    "info": {
        "name": "Staff API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Get All Staff",
            "request": {
                "method": "GET",
                "header": [],
                "url": {
                    "raw": "{{base_url}}/api/staff?per_page=15",
                    "host": ["{{base_url}}"],
                    "path": ["api", "staff"],
                    "query": [
                        {
                            "key": "per_page",
                            "value": "15"
                        },
                        {
                            "key": "search",
                            "value": "",
                            "disabled": true
                        },
                        {
                            "key": "is_active",
                            "value": "true",
                            "disabled": true
                        }
                    ]
                }
            }
        },
        {
            "name": "Get Staff by ID",
            "request": {
                "method": "GET",
                "header": [],
                "url": {
                    "raw": "{{base_url}}/api/staff/1",
                    "host": ["{{base_url}}"],
                    "path": ["api", "staff", "1"]
                }
            }
        },
        {
            "name": "Create Staff",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"name\": \"John Doe\",\n  \"email\": \"john@example.com\",\n  \"phone\": \"08123456789\",\n  \"is_active\": true,\n  \"salary\": 5000000\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/staff",
                    "host": ["{{base_url}}"],
                    "path": ["api", "staff"]
                }
            }
        },
        {
            "name": "Update Staff",
            "request": {
                "method": "PUT",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"phone\": \"08198765432\",\n  \"salary\": 7000000\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/staff/1",
                    "host": ["{{base_url}}"],
                    "path": ["api", "staff", "1"]
                }
            }
        },
        {
            "name": "Delete Staff",
            "request": {
                "method": "DELETE",
                "header": [],
                "url": {
                    "raw": "{{base_url}}/api/staff/1",
                    "host": ["{{base_url}}"],
                    "path": ["api", "staff", "1"]
                }
            }
        }
    ],
    "variable": [
        {
            "key": "base_url",
            "value": "http://localhost:8000",
            "type": "string"
        }
    ]
}
```

---

## CORS Configuration

Jika API akan diakses dari domain lain (frontend terpisah), pastikan CORS sudah dikonfigurasi:

### File: `config/cors.php`

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // Ubah ke domain spesifik di production
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

---

## Authentication (Optional)

Jika ingin menambahkan authentication menggunakan Laravel Sanctum:

### 1. Install Sanctum (jika belum):

```bash
php artisan install:api
```

### 2. Update routes di `routes/api.php`:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('staff', StaffController::class);
});
```

### 3. Generate token untuk user:

```php
$token = $user->createToken('api-token')->plainTextToken;
```

### 4. Gunakan token di header:

```bash
curl -X GET "http://localhost:8000/api/staff" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Error Handling

### Common HTTP Status Codes:

-   `200 OK` - Request berhasil
-   `201 Created` - Resource berhasil dibuat
-   `204 No Content` - Request berhasil tanpa response body
-   `400 Bad Request` - Request tidak valid
-   `401 Unauthorized` - Authentication required
-   `403 Forbidden` - Tidak memiliki akses
-   `404 Not Found` - Resource tidak ditemukan
-   `422 Unprocessable Entity` - Validasi gagal
-   `500 Internal Server Error` - Server error

### Example Error Response:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["Email sudah digunakan"],
        "department_reference_id": ["Department tidak ditemukan"]
    }
}
```

---

## Tips & Best Practices

1. **Pagination**: Selalu gunakan pagination untuk list data yang besar
2. **Filtering**: Kombinasikan filter untuk mendapatkan data spesifik
3. **Error Handling**: Tangani error response dengan baik di aplikasi client
4. **Rate Limiting**: Pertimbangkan rate limiting untuk production
5. **Caching**: Gunakan cache untuk endpoint yang sering diakses
6. **Versioning**: Pertimbangkan API versioning (v1, v2) untuk perubahan breaking changes

---

## Support

Untuk pertanyaan atau issue, silakan hubungi tim development.
