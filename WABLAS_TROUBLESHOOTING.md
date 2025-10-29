# Wablas WhatsApp Integration - Troubleshooting Guide

## Error: "Gagal mengirim slip gaji PDF: error sending document message"

### Deskripsi Masalah

Ketika mengirim slip gaji PDF via WhatsApp menggunakan Wablas API, muncul error:

-   **HTTP Code**: 500
-   **Message**: "error sending document message"
-   Status: Pesan teks berhasil dikirim, tapi PDF gagal

### Penyebab Umum

#### 1. Device WhatsApp Offline

**Penyebab paling umum!** Device WhatsApp yang terhubung ke Wablas harus online dan terhubung.

**Cara Mengecek:**

```bash
php artisan wablas:test
```

**Solusi:**

-   Login ke dashboard Wablas: https://texas.wablas.com
-   Pastikan device WhatsApp Anda terkoneksi (status hijau)
-   Scan QR code jika device terputus
-   Pastikan HP dengan WhatsApp Business tetap terhubung internet

#### 2. Quota/Limit Tercapai

Wablas memiliki limit pengiriman per hari/bulan tergantung paket.

**Solusi:**

-   Cek dashboard Wablas untuk melihat sisa quota
-   Upgrade paket jika sudah habis
-   Tunggu reset quota (biasanya midnight atau per bulan)

#### 3. Ukuran File Terlalu Besar

WhatsApp membatasi ukuran dokumen maksimal 10MB.

**Sudah Dihandle:** Sistem sudah validasi otomatis, max 10MB.

**Jika masih error:**

-   Reduce PDF size dengan compress gambar/logo
-   Kurangi jumlah halaman jika memungkinkan

#### 4. Format File Bermasalah

PDF harus sesuai standard WhatsApp.

**Sudah Dihandle:** Menggunakan DomPDF v3.1.1 yang compatible.

#### 5. Authorization Issue

Token atau Secret Key tidak sesuai untuk endpoint document.

**Cara Mengecek:**

```bash
# Cek file .env
grep WABLAS .env
```

**Yang Harus Ada:**

```env
WABLAS_TOKEN=your_token_here
WABLAS_SECRET_KEY=your_secret_key_here
WABLAS_BASE_URL=https://texas.wablas.com/api
WABLAS_AUTH_HEADER=concat
```

**Auth Header Style:**

-   `concat`: Authorization: {token}.{secretKey} (default)
-   `token`: Authorization: {token}
-   `bearer`: Authorization: Bearer {token}

Sesuaikan dengan instance Wablas Anda.

### Langkah Debugging

#### 1. Test Koneksi Wablas

```bash
php artisan wablas:test 628xxxxx
```

#### 2. Cek Log Laravel

```bash
tail -f storage/logs/laravel.log | grep -i wablas
```

#### 3. Test Manual di Dashboard Wablas

-   Login ke https://texas.wablas.com
-   Coba kirim dokumen manual
-   Jika manual juga gagal = masalah di device/account

#### 4. Cek Detail Error

Log akan menampilkan:

-   HTTP Code
-   Response message
-   File size
-   Phone number (formatted)
-   Full API response

### Solusi Sementara

Jika dokumen tidak bisa dikirim, Anda bisa:

#### Option 1: Kirim Link Download

Ubah logic untuk:

1. Upload PDF ke server/storage
2. Generate link download
3. Kirim link via WhatsApp text message

#### Option 2: Kirim via Email

Tambah fallback untuk kirim PDF via email jika WhatsApp gagal.

#### Option 3: Download Manual

Tambah tombol "Download PDF" di tabel, biarkan user download dan kirim manual.

### Implementasi Fallback

Edit `PayrollWhatsAppController.php`:

```php
public function sendPayslipWithPdf(PayrollDetail $detail)
{
    // ... existing code ...

    $result = $this->wablasService->sendPayslipWithPdf(...);

    if (!$result['success']) {
        // FALLBACK: Generate download link
        $publicPath = public_path('storage/payslips/');
        if (!file_exists($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        $filename = 'slip_gaji_' . $detail->staff->id . '_' . time() . '.pdf';
        $publicFile = $publicPath . $filename;
        copy($pdfPath, $publicFile);

        $downloadUrl = url('storage/payslips/' . $filename);

        // Kirim link via WhatsApp
        $fallbackMessage = "📋 *SLIP GAJI RAFATAX*\n\n";
        $fallbackMessage .= "Dokumen PDF tidak dapat dikirim langsung.\n";
        $fallbackMessage .= "Silakan download di: {$downloadUrl}\n\n";
        $fallbackMessage .= "Link berlaku 24 jam.";

        $this->wablasService->sendMessage($phone, $fallbackMessage);

        return response()->json([
            'success' => true,
            'message' => 'Slip gaji dikirim dengan link download (fallback mode)'
        ]);
    }
}
```

### Contact Support Wablas

Jika semua solusi tidak berhasil:

-   Email: support@wablas.com
-   WhatsApp: Cek di dashboard
-   Telegram: Join grup support Wablas

Berikan informasi:

-   Token Anda (jangan share secret key)
-   Error message lengkap
-   Screenshot dari dashboard
-   Timestamp error

### Monitoring

Untuk memantau status pengiriman:

```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log | grep "Wablas\|slip gaji"

# Cek error logs hari ini
grep "Wablas.*error" storage/logs/laravel-$(date +%Y-%m-%d).log
```

### Improvement Recommendations

1. **Add Retry Logic**: Otomatis retry 3x jika gagal
2. **Queue System**: Gunakan Laravel Queue untuk pengiriman async
3. **Status Tracking**: Simpan status pengiriman ke database
4. **Dashboard Monitor**: Buat halaman monitoring pengiriman
5. **Alternative Provider**: Siapkan backup provider (Fonnte, dll)

---

**Last Updated**: October 29, 2025
**Version**: 1.0
