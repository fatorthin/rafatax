# Perbaikan Konfigurasi WAblas Service

## 📋 Ringkasan Perubahan

Setelah membandingkan dengan dokumentasi resmi WAblas API di https://texas.wablas.com/documentation/api, ditemukan **3 kesalahan utama** dalam implementasi sebelumnya yang telah diperbaiki.

---

## ❌ Masalah yang Ditemukan

### 1. **Format Authorization Header Salah**

#### Sebelum (Salah):

```php
// Menggunakan multiple format yang tidak konsisten
"Authorization: {$token}"
"Secret: {$secretKey}"  // Header terpisah yang tidak ada di dokumentasi
```

#### Setelah (Benar):

```php
// Sesuai dokumentasi WAblas
"Authorization: {$token}.{$secretKey}"
```

**Referensi Dokumentasi:**

```php
// Dokumentasi WAblas - Single Send Document
curl_setopt($curl, CURLOPT_HTTPHEADER,
    array(
        "Authorization: $token.$secret_key",
    )
);
```

---

### 2. **Endpoint yang Salah untuk Upload File Lokal**

#### Sebelum (Salah):

```php
// Menggunakan endpoint untuk URL file, bukan file lokal
CURLOPT_URL => $this->baseUrl . "/send-document"
CURLOPT_POSTFIELDS => ['document' => new CURLFile(...)]
```

#### Setelah (Benar):

```php
// Menggunakan endpoint khusus untuk file lokal dengan base64
CURLOPT_URL => $this->baseUrl . "/send-document-from-local"
CURLOPT_POSTFIELDS => http_build_query([
    'phone' => $phone,
    'file' => base64_encode(file_get_contents($filePath)),
    'data' => json_encode(['name' => $fileName])
])
```

**Perbedaan Penting:**

| Endpoint                    | Fungsi                  | Parameter                       | Format                |
| --------------------------- | ----------------------- | ------------------------------- | --------------------- |
| `/send-document`            | Kirim document dari URL | `document` (URL)                | x-www-form-urlencoded |
| `/send-document-from-local` | Upload file lokal       | `file` (base64) + `data` (JSON) | x-www-form-urlencoded |

**Referensi Dokumentasi:**

```php
// Dokumentasi WAblas - Send Document from Local
$file = '/path/to/your_pdf_file.pdf';

$data = [
    'phone' => '6281393961320',
    'file' => base64_encode(file_get_contents($file)),
    'data' => json_encode(['name' => 'your_pdf_file.pdf'])
];
```

---

### 3. **Parameter Request Body Tidak Sesuai**

#### Sebelum (Salah):

```php
$data = [
    'phone' => $phone,
    'document' => new CURLFile($path),  // CURLFile tidak didukung di endpoint ini
    'caption' => $caption,
    'secret' => $this->secretKey  // Secret key sudah ada di header
];
```

#### Setelah (Benar):

```php
$data = [
    'phone' => $phone,
    'file' => base64_encode(file_get_contents($path)),  // Base64 encoded
    'data' => json_encode(['name' => $fileName])  // Metadata sebagai JSON string
];
```

---

## ✅ Perubahan Detail

### File: `app/Services/WablasService.php`

#### 1. Method `sendMessage()`

```php
// BEFORE
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    $this->buildAuthHeader(),  // Dynamic auth style
]);

// AFTER
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: {$this->token}.{$this->secretKey}",  // Fixed format
]);
```

#### 2. Method `sendDocument()`

```php
// BEFORE - Endpoint Salah
CURLOPT_URL => $this->baseUrl . "/send-document"
CURLOPT_POSTFIELDS => [
    'document' => new CURLFile($path),
    'secret' => $this->secretKey
]
CURLOPT_HTTPHEADER => [
    "Authorization: {$this->token}",
    "Secret: {$this->secretKey}"
]

// AFTER - Endpoint Benar
CURLOPT_URL => $this->baseUrl . "/send-document-from-local"
CURLOPT_POSTFIELDS => http_build_query([
    'phone' => $phone,
    'file' => base64_encode(file_get_contents($path)),
    'data' => json_encode(['name' => basename($path)])
])
CURLOPT_HTTPHEADER => [
    "Authorization: {$this->token}.{$this->secretKey}"
]
```

#### 3. Removed

-   ❌ Property `$authHeaderStyle` (tidak diperlukan)
-   ❌ Method `buildAuthHeader()` (diganti dengan format fixed)
-   ❌ Header `Secret: {$secretKey}` (secret key sudah di Authorization)

---

## 🔍 Mengapa IP Whitelist Tetap Diperlukan?

Meskipun format request sudah benar, **error 403 masih bisa terjadi** karena:

1. **IP Whitelist Requirement**

    - WAblas endpoint tertentu memerlukan IP whitelist
    - IP server Anda: `202.6.192.2`
    - Harus didaftarkan di dashboard WAblas

2. **Secret Key Verification**

    - Secret key di Authorization header: sudah benar ✅
    - Format: `{token}.{secret_key}` sesuai dokumentasi

3. **Fallback System Tetap Aktif**
    - Jika endpoint `/send-document-from-local` tetap 403
    - System otomatis copy PDF ke `public/storage/payslips/`
    - Kirim download link via message text
    - User tetap dapat akses slip gaji

---

## 📝 Cara Whitelist IP di WAblas

1. Login ke dashboard: https://texas.wablas.com
2. Pilih menu **Device → Settings**
3. Cari section **IP Whitelist** atau **Security**
4. Tambahkan IP: `202.6.192.2`
5. Save dan tunggu propagasi (1-5 menit)

---

## 🧪 Testing Setelah Perbaikan

### Test 1: Kirim Message (Sudah Berhasil)

```bash
php artisan tinker

$service = new App\Services\WablasService();
$service->sendMessage('628123456789', 'Test message');
```

**Status:** ✅ **BERHASIL** (200 OK)

### Test 2: Kirim Document (Perlu Test Ulang)

```bash
php artisan tinker

$service = new App\Services\WablasService();
$service->sendDocument('628123456789', storage_path('app/temp/test.pdf'), 'Test PDF');
```

**Status Sebelumnya:** ❌ **403 Forbidden**  
**Status Setelah Perbaikan:** 🔄 **Perlu dicoba ulang**

Jika masih 403 → Fallback ke link download (tetap berfungsi)

---

## 📖 Referensi Dokumentasi WAblas

### Send Message (Text)

-   **URL:** `POST /send-message`
-   **Auth:** `Authorization: {token}.{secret_key}`
-   **Body:** `phone`, `message`
-   **Docs:** https://texas.wablas.com/documentation/api#single-send-text

### Send Document from Local

-   **URL:** `POST /send-document-from-local`
-   **Auth:** `Authorization: {token}.{secret_key}`
-   **Body:**
    -   `phone` (string)
    -   `file` (base64 string)
    -   `data` (JSON string: `{"name": "filename.pdf"}`)
-   **Max Size:** 2MB
-   **Supported:** doc, docx, pdf, odt, csv, ppt, pptx, xls, xlsx, txt
-   **Docs:** https://texas.wablas.com/documentation/api#send-document-local

### Send Document from URL

-   **URL:** `POST /send-document`
-   **Auth:** `Authorization: {token}.{secret_key}`
-   **Body:** `phone`, `document` (URL string), `caption`
-   **Docs:** https://texas.wablas.com/documentation/api#single-send-document

---

## 🎯 Kesimpulan

### Yang Sudah Benar Sekarang:

1. ✅ Format Authorization Header sesuai dokumentasi
2. ✅ Endpoint yang tepat (`/send-document-from-local`)
3. ✅ Format request body (base64 + JSON metadata)
4. ✅ Content-Type otomatis dari http_build_query()
5. ✅ Fallback system untuk redundansi

### Next Steps:

1. 🔄 **Test ulang** kirim document dengan format baru
2. 🔐 **Whitelist IP** `202.6.192.2` di dashboard WAblas (opsional)
3. ✅ **Verifikasi** fallback system tetap berfungsi jika tetap 403

### Hasil yang Diharapkan:

-   **Scenario 1:** IP sudah di-whitelist → PDF terkirim langsung ✅
-   **Scenario 2:** IP belum di-whitelist → PDF terkirim via link download ✅
-   **Kedua scenario:** User tetap mendapat slip gaji 🎉

---

**Created:** 2025-11-01  
**Last Updated:** 2025-11-01  
**Status:** ✅ Ready for Testing
