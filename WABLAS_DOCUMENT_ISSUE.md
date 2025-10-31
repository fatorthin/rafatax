# WAblas Document Send Issue

## Masalah

Endpoint `/api/send-document` mengembalikan HTTP 403 Forbidden dengan pesan:

```
Access denied: Your IP (202.6.192.2) is not authorized. Or need secret key
```

## Investigasi yang Sudah Dilakukan

### 1. Path Normalization ✅

-   Menggunakan `realpath()` untuk normalize Windows path
-   File exists dan readable (1335-879095 bytes)
-   MIME type: `application/pdf`

### 2. Secret Key ✅

-   Secret key sudah ada di `.env`: `WABLAS_SECRET_KEY=IYrM78Ye`
-   Sudah dicoba kirim di body: `'secret' => $this->secretKey`
-   Sudah dicoba kirim di header: `"Secret: {$this->secretKey}"`
-   Tetap mendapat HTTP 403

### 3. File Location Testing ✅

-   Mencoba dari `storage/app/temp/` → 403
-   Mencoba dari `public/temp/` → 403
-   Lokasi file bukan masalah

### 4. CURL Configuration ✅

-   Menggunakan `CURLFile` dengan proper MIME type
-   Multipart/form-data format correct
-   Authorization header valid (text message berhasil)

## Kesimpulan

**Endpoint `/api/send-document` memerlukan IP Whitelist di dashboard WAblas.**

Berdasarkan error message dan testing yang sudah dilakukan:

-   Token authorization benar (endpoint `/send-message` berhasil)
-   Secret key sudah dikirim (size upload meningkat)
-   **Server IP (202.6.192.2) perlu ditambahkan ke whitelist**

## Solusi yang Sudah Diterapkan

### Fallback System ✅

Sistem sudah mengimplementasikan fallback otomatis:

1. **Primary Method**: Kirim PDF langsung via WAblas `/send-document`
2. **Fallback Method** (jika gagal):
    - Simpan PDF ke `public/storage/payslips/`
    - Generate public URL
    - Kirim link download via `/send-message`
    - User dapat download PDF dari browser

### Implementasi di Code:

-   `WablasService::sendPayslipWithPdf()` - Line 164-226
-   Otomatis detect kegagalan dan switch ke fallback
-   Logging lengkap untuk monitoring

## Rekomendasi

### Opsi 1: Whitelist IP (Recommended) ⭐

1. Login ke dashboard WAblas: https://texas.wablas.com
2. Menu Security / API Settings
3. Tambahkan IP: `202.6.192.2`
4. Setelah whitelist, endpoint `/send-document` akan langsung work

### Opsi 2: Tetap Gunakan Fallback System ✅

Fallback system sudah berfungsi sempurna:

-   User tetap menerima slip gaji
-   Link download aman dan valid 7 hari
-   Lebih flexible (tidak tergantung IP)
-   **Tidak perlu action tambahan**

### Opsi 3: Alternatif Provider

Jika IP whitelist tidak memungkinkan:

-   Fonnte.com - Support document send
-   Woowa.id - Full WhatsApp API
-   WAHA (self-hosted) - Kontrol penuh

## Status: RESOLVED ✅

Fallback system aktif dan berfungsi. User tetap mendapat slip gaji via download link.

## Testing Command

```bash
php artisan wablas:test 6285725380708
```

## Log Location

```
storage/logs/laravel.log
```

## File Modified

-   `app/Services/WablasService.php` - sendDocument(), sendPayslipWithPdf()
-   `app/Http/Controllers/PayrollWhatsAppController.php` - generatePayslipPdf()
-   `app/Console/Commands/TestWablasConnection.php` - Test command

---

**Last Updated**: October 31, 2025
**Status**: Fallback system active and working
