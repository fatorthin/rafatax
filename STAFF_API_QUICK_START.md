# Staff API - Quick Start Guide

## 🚀 Setup

API sudah siap digunakan! File-file yang telah dibuat:

### Controllers

-   `app/Http/Controllers/API/StaffController.php` - API Controller

### Resources

-   `app/Http/Resources/StaffResource.php` - Single resource transformer
-   `app/Http/Resources/StaffCollection.php` - Collection transformer

### Requests (Validation)

-   `app/Http/Requests/StaffStoreRequest.php` - Validasi untuk create
-   `app/Http/Requests/StaffUpdateRequest.php` - Validasi untuk update

### Routes

-   `routes/api.php` - API routes
-   `bootstrap/app.php` - Updated untuk load API routes

---

## 📋 Available Endpoints

| Method    | Endpoint          | Description                        |
| --------- | ----------------- | ---------------------------------- |
| GET       | `/api/staff`      | List semua staff (with pagination) |
| GET       | `/api/staff/{id}` | Detail staff by ID                 |
| POST      | `/api/staff`      | Create staff baru                  |
| PUT/PATCH | `/api/staff/{id}` | Update staff                       |
| DELETE    | `/api/staff/{id}` | Delete staff (soft delete)         |

---

## 🧪 Quick Test

### 1. List All Staff

```bash
curl http://localhost:8000/api/staff
```

### 2. Get Staff by ID

```bash
curl http://localhost:8000/api/staff/1
```

### 3. Create New Staff

```bash
curl -X POST http://localhost:8000/api/staff \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "08123456789",
    "is_active": true
  }'
```

### 4. Update Staff

```bash
curl -X PUT http://localhost:8000/api/staff/1 \
  -H "Content-Type: application/json" \
  -d '{"phone": "08198765432"}'
```

### 5. Delete Staff

```bash
curl -X DELETE http://localhost:8000/api/staff/1
```

---

## 🔍 Query Parameters

### Pagination

```bash
# 10 items per page
curl "http://localhost:8000/api/staff?per_page=10"

# Page 2
curl "http://localhost:8000/api/staff?page=2"
```

### Search

```bash
# Search by name, email, phone, or no_ktp
curl "http://localhost:8000/api/staff?search=john"
```

### Filtering

```bash
# Active staff only
curl "http://localhost:8000/api/staff?is_active=true"

# By department
curl "http://localhost:8000/api/staff?department_id=1"

# By position
curl "http://localhost:8000/api/staff?position_id=2"
```

### Sorting

```bash
# Sort by name ascending
curl "http://localhost:8000/api/staff?sort_by=name&sort_direction=asc"

# Sort by salary descending
curl "http://localhost:8000/api/staff?sort_by=salary&sort_direction=desc"
```

### Combined

```bash
curl "http://localhost:8000/api/staff?search=john&is_active=true&department_id=1&per_page=20&sort_by=name&sort_direction=asc"
```

---

## 📦 Response Format

### Success Response (List)

```json
{
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "08123456789",
            "is_active": true,
            "department": {
                "id": 1,
                "name": "Finance"
            },
            "position": {
                "id": 2,
                "name": "Staff"
            },
            "created_at": "2024-01-01T00:00:00.000000Z"
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
        "per_page": 15,
        "total": 65,
        "version": "1.0.0"
    }
}
```

### Success Response (Single)

```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "department": {...},
    "position": {...},
    "clients": [...],
    "trainings": [...]
  },
  "meta": {
    "version": "1.0.0"
  }
}
```

### Error Response (Validation)

```json
{
    "message": "The name field is required.",
    "errors": {
        "name": ["Nama staff wajib diisi"],
        "email": ["Email sudah digunakan"]
    }
}
```

---

## 🔐 Optional: Add Authentication

Jika ingin menambahkan authentication dengan Laravel Sanctum:

### 1. Install Sanctum

```bash
php artisan install:api
```

### 2. Update `routes/api.php`

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('staff', StaffController::class);
});
```

### 3. Generate Token

```php
$token = $user->createToken('api-token')->plainTextToken;
```

### 4. Use Token in Request

```bash
curl http://localhost:8000/api/staff \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 🌐 CORS Setup

Jika API diakses dari domain berbeda, update `config/cors.php`:

```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // atau ['http://your-frontend-domain.com']
    'allowed_headers' => ['*'],
];
```

---

## 📚 Full Documentation

Lihat dokumentasi lengkap di: `STAFF_API_DOCUMENTATION.md`

---

## ✅ Validation Rules

### Required Fields (Create)

-   `name` (string, max: 255)

### Optional Fields

-   `email` (email, unique)
-   `no_ktp` (string, unique)
-   `phone`, `address`, `birth_place`, `birth_date`
-   `jenjang`, `jurusan`, `university`, `no_ijazah`
-   `tmt_training`, `periode`, `selesai_training`
-   `department_reference_id`, `position_reference_id`
-   `is_active` (boolean)
-   `salary` (numeric, min: 0)
-   `position_status`

---

## 🎯 Features

✅ RESTful API endpoints
✅ Pagination support
✅ Search functionality
✅ Advanced filtering
✅ Sorting
✅ Eloquent Resources (data transformation)
✅ Form Request Validation
✅ Soft Delete
✅ Relationships (department, position, clients, trainings)
✅ Custom error messages

---

## 🐛 Troubleshooting

### Route tidak muncul?

```bash
php artisan route:clear
php artisan route:cache
```

### API tidak bisa diakses?

Pastikan `bootstrap/app.php` sudah include `api` routes.

### Validation error?

Check `app/Http/Requests/Staff*Request.php` untuk melihat rules.

---

## 📞 Support

Untuk pertanyaan lebih lanjut, silakan hubungi tim development.
