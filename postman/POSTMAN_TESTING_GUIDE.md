# Postman Testing Guide - Client API

## 📦 Files Yang Disediakan

1. **Client_API_Collection.postman_collection.json** - Collection utama untuk testing
2. **Rafatax_Local.postman_environment.json** - Environment untuk development lokal
3. **Rafatax_Production.postman_environment.json** - Environment untuk production

## 🚀 Cara Import ke Postman

### Method 1: Import via Postman App

1. **Buka Postman**
2. **Klik "Import"** di pojok kiri atas
3. **Drag & Drop atau Browse** file berikut:
    - `Client_API_Collection.postman_collection.json`
    - `Rafatax_Local.postman_environment.json`
    - `Rafatax_Production.postman_environment.json`
4. **Klik "Import"**

### Method 2: Import via File Menu

1. Buka Postman
2. File → Import
3. Pilih ketiga file JSON
4. Klik Import

## ⚙️ Setup Environment

### 1. Pilih Environment

Di pojok kanan atas Postman, pilih environment:

-   **Rafatax - Local Development** untuk testing lokal
-   **Rafatax - Production** untuk testing production

### 2. Edit Environment Variables

Klik icon mata (eye) atau gear di pojok kanan atas, lalu edit:

#### Local Development:

```
base_url: http://localhost/api
admin_email: admin@rafatax.com
admin_password: password
```

#### Production:

```
base_url: https://your-domain.com/api
admin_email: admin@rafatax.com
admin_password: your-production-password
```

## 📝 Request Collection Structure

### 1. Authentication

-   **Login** - Get authentication token
-   **Logout** - Revoke token

### 2. Clients (CRUD)

-   **Get All Clients** - List dengan pagination
-   **Get All Clients (with Search)** - Search functionality
-   **Get All Clients (with Filters)** - Filter by status, type, etc.
-   **Get Single Client** - Detail client dengan relationships
-   **Create New Client** - Create dengan semua fields
-   **Create Client (Minimal)** - Create dengan required fields only
-   **Update Client** - Update existing client
-   **Delete Client (Soft Delete)** - Soft delete
-   **Restore Deleted Client** - Restore soft deleted client
-   **Force Delete Client** - Permanent delete

### 3. Negative Tests

-   **Get Client - Not Found** - Test 404 error
-   **Create Client - Duplicate Code** - Test validation error
-   **Create Client - Missing Required Fields** - Test validation
-   **Access Without Token** - Test 401 unauthorized

## 🔄 Testing Flow (Recommended Order)

### Step 1: Authentication

1. Jalankan request **Login**
    - Token akan otomatis tersimpan di environment variable `auth_token`
    - Semua request selanjutnya akan menggunakan token ini

### Step 2: Read Operations

2. **Get All Clients** - Lihat daftar client
    - Client ID pertama akan tersimpan di `client_id`
3. **Get Single Client** - Lihat detail client
4. **Get All Clients (with Search)** - Test search
5. **Get All Clients (with Filters)** - Test filter

### Step 3: Create Operations

6. **Create New Client** - Buat client baru
    - ID client baru akan tersimpan di `new_client_id`
    - Unique code akan di-generate otomatis
7. **Create Client (Minimal)** - Test minimal fields

### Step 4: Update Operations

8. **Update Client** - Update client yang baru dibuat

### Step 5: Delete Operations

9. **Delete Client (Soft Delete)** - Soft delete
10. **Restore Deleted Client** - Restore
11. **Delete Client (Soft Delete)** - Delete lagi
12. **Force Delete Client** - Permanent delete

### Step 6: Error Handling Tests

13. **Get Client - Not Found**
14. **Create Client - Duplicate Code**
15. **Create Client - Missing Required Fields**
16. **Access Without Token**

## ✅ Automated Tests

Setiap request sudah dilengkapi dengan **automated tests** yang akan:

-   ✅ Verify status code
-   ✅ Validate response structure
-   ✅ Check required fields
-   ✅ Measure response time
-   ✅ Auto-save IDs ke environment variables

### Melihat Test Results:

1. Jalankan request
2. Lihat tab **"Test Results"** di bawah response
3. Green checkmarks = test passed ✅
4. Red X = test failed ❌

## 🔧 Environment Variables (Auto-Generated)

Variables berikut akan otomatis ter-set saat menjalankan requests:

| Variable          | Deskripsi                        | Set By Request     |
| ----------------- | -------------------------------- | ------------------ |
| `auth_token`      | Bearer token                     | Login              |
| `client_id`       | ID client pertama dari list      | Get All Clients    |
| `new_client_id`   | ID client yang baru dibuat       | Create New Client  |
| `unique_code`     | Unique code untuk create         | Pre-request Script |
| `unique_code_min` | Unique code untuk minimal create | Pre-request Script |

## 📊 Running Collection

### Run Semua Tests Sekaligus:

1. Klik tombol **"Run"** di collection
2. Pilih **"Rafatax - Client API"**
3. Pilih environment
4. Klik **"Run Rafatax - Client API"**
5. Lihat hasil test summary

### Tips untuk Collection Runner:

-   ✅ Jalankan "Login" dulu sebelum requests lain
-   ✅ Set delay 100-500ms antar request
-   ✅ Uncheck "Force Delete" jika ingin preserve data
-   ✅ Check "Save responses" untuk debugging

## 🎯 Query Parameters Guide

### Get All Clients - Available Parameters:

```
?per_page=15              # Items per page (default: 15)
&search=PT                # Search term
&status=active            # Filter by status
&type=PT                  # Filter by type
&grade=A                  # Filter by grade
&jenis_wp=Badan          # Filter by jenis WP
&sort_by=created_at       # Sort field
&sort_direction=desc      # Sort direction (asc/desc)
```

### Contoh Kombinasi:

```
/clients?search=PT&status=active&per_page=10&sort_by=company_name&sort_direction=asc
```

## 🔍 Request Examples

### Create Client - Full Fields:

```json
{
    "code": "CLT001",
    "company_name": "PT Example Indonesia",
    "phone": "081234567890",
    "address": "Jl. Example No. 123",
    "owner_name": "John Doe",
    "owner_role": "CEO",
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
    "type": "PT"
}
```

### Update Client - Partial Update:

```json
{
    "company_name": "PT Updated Name",
    "phone": "081234567899",
    "status": "inactive"
}
```

## 🐛 Troubleshooting

### Problem: "Unauthenticated" Error (401)

**Solution:**

1. Jalankan request "Login" terlebih dahulu
2. Pastikan token tersimpan di environment variable `auth_token`
3. Check Authorization tab → Type: Bearer Token → Token: {{auth_token}}

### Problem: "The code has already been taken" (422)

**Solution:**

1. Code harus unique
2. Request "Create New Client" sudah auto-generate unique code
3. Jika manual, pastikan code berbeda

### Problem: "No query results for model" (404)

**Solution:**

1. Pastikan `client_id` atau `new_client_id` sudah ter-set
2. Jalankan "Get All Clients" dulu untuk set client_id
3. Atau jalankan "Create New Client" untuk set new_client_id

### Problem: Variables tidak ter-save

**Solution:**

1. Pastikan environment sudah dipilih (pojok kanan atas)
2. Check tab "Tests" pada request
3. Lihat Console (View → Show Postman Console) untuk debug

## 📈 Best Practices

1. **Selalu pilih environment** sebelum testing
2. **Run Login dulu** sebelum request lain
3. **Check test results** setelah setiap request
4. **Use Collection Runner** untuk regression testing
5. **Save responses** untuk dokumentasi
6. **Monitor response time** (target: < 2000ms)
7. **Clean up test data** dengan Force Delete setelah testing

## 🎓 Advanced Features

### Pre-request Scripts

Request "Create" sudah dilengkapi dengan script untuk generate unique code:

```javascript
var timestamp = Date.now();
pm.environment.set("unique_code", "TEST-" + timestamp);
```

### Test Scripts

Setiap request memiliki automated tests:

```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});
```

### Chaining Requests

Variables digunakan untuk chain requests:

1. Login → save `auth_token`
2. Get All → save `client_id`
3. Get Single → use `client_id`
4. Create → save `new_client_id`
5. Update → use `new_client_id`

## 📞 Support

Jika ada masalah atau pertanyaan:

1. Check Console (View → Show Postman Console)
2. Review API Documentation: `CLIENT_API_DOCUMENTATION.md`
3. Check Quick Reference: `CLIENT_API_QUICK_REFERENCE.md`

---

**Happy Testing! 🚀**

Created: 2025-11-03  
Version: 1.0.0
