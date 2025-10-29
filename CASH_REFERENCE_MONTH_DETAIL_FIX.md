# Fix: Cash Reference Month Detail - Error "month must be between 0 and 99, -1 given"

## Masalah

Saat mengklik tombol delete pada halaman detail transaksi bulanan (ViewCashReferenceMonthDetail), muncul error:

```
month must be between 0 and 99, -1 given
```

## Akar Masalah

Error terjadi karena:

1. Filament's default DeleteAction melakukan refresh/redirect halaman setelah delete
2. Saat refresh, query parameter `month` hilang atau menjadi invalid (-1)
3. Kode di `getTitle()` dan blade view memanggil `Carbon::create()->month($month)` dengan nilai month yang invalid
4. Carbon melempar exception karena month harus antara 1-12

## Solusi

Membuat **halaman view custom** yang tidak bergantung pada Filament table system:

### 1. Controller Baru

**File**: `app/Http/Controllers/CashReferenceMonthController.php`

-   Method `show()`: Menampilkan detail transaksi bulanan dengan validasi parameter
-   Method `store()`: Menyimpan transaksi baru via AJAX (dengan validasi)
-   Method `delete()`: Menghapus transaksi dan redirect kembali dengan parameter yang benar

### 2. Blade View Custom

**File**: `resources/views/cash-reference-month-detail.blade.php`

-   Standalone HTML dengan Tailwind CSS & Font Awesome
-   Tabel transaksi dengan saldo berjalan (running balance)
-   **Modal create transaction** dengan form lengkap (AJAX submission)
-   Modal konfirmasi delete
-   Tombol CRUD lengkap: View, Edit, Delete, Add (via modal)
-   Menampilkan:
    -   Saldo awal (dari bulan sebelumnya)
    -   Semua transaksi dengan balance berjalan
    -   Total debit, total credit
    -   Saldo akhir

### 3. Routing

**File**: `routes/web.php`

```php
// Menampilkan detail transaksi bulanan
GET /cash-reference/{id}/month-detail

// Menyimpan transaksi baru (AJAX)
POST /cash-reference/{id}/transaction/store

// Menghapus transaksi
DELETE /cash-reference/transaction/{transactionId}/delete
```

### 4. Update Link

**File**: `app/Filament/Resources/CashReferenceResource/Pages/ViewCashReferenceMonthly.php`

-   Mengubah link "View Transactions" untuk mengarah ke halaman custom
-   Dari: `CashReferenceResource::getUrl('monthDetail', ...)`
-   Ke: `route('cash-reference.month-detail', ...)`

## Fitur Halaman Baru

### Tampilan

-   ✅ Header dengan judul Cash Reference dan bulan/tahun
-   ✅ Card saldo awal (dengan warna hijau/merah sesuai positif/negatif)
-   ✅ Tabel transaksi responsif dengan Tailwind CSS
-   ✅ Alert success/error setelah operasi CRUD

### Fungsi CRUD

-   ✅ **View**: Melihat semua transaksi bulan tersebut
-   ✅ **Create**: Modal form dengan field lengkap (Transaction Date, CoA, Description, Debit, Credit)
    -   Tombol "Save": Simpan dan tutup modal (refresh halaman)
    -   Tombol "Save & Create Another": Simpan dan reset form (tetap di modal untuk input berikutnya)
-   ✅ **Edit**: Link ke form edit di Filament (cash-reports.edit)
-   ✅ **Delete**: Modal konfirmasi, lalu hapus dan refresh halaman dengan parameter yang sama

### Perhitungan

-   ✅ Saldo awal: Sum semua transaksi sampai akhir bulan sebelumnya
-   ✅ Running balance: Saldo berjalan per transaksi
-   ✅ Total debit dan credit per bulan
-   ✅ Saldo akhir bulan

### Navigasi

-   ✅ Back to Monthly View (url()->previous())
-   ✅ Add Transaction (button yang membuka modal)

## Cara Menggunakan

1. Buka halaman Monthly Transaction (Cash Reference → View Monthly)
2. Klik "View Transactions" pada salah satu bulan
3. Anda akan diarahkan ke halaman custom baru
4. Gunakan tombol CRUD:
    - **Add Transaction**: Klik tombol biru → Modal form terbuka
        - Isi form (Transaction Date, CoA, Description, Debit, Credit)
        - Klik "Save" untuk simpan dan tutup modal
        - Klik "Save & Create Another" untuk simpan dan lanjut input data berikutnya
    - **Edit** (icon pensil): Edit transaksi di Filament
    - **Delete** (icon sampah): Hapus dengan konfirmasi modal

## Testing

Untuk memastikan semua berfungsi:

```bash
# Cek syntax errors
php -l app/Http/Controllers/CashReferenceMonthController.php
php -l routes/web.php
php -l app/Filament/Resources/CashReferenceResource/Pages/ViewCashReferenceMonthly.php

# Cek routes terdaftar
php artisan route:list --name=cash-reference
```

## File yang Dibuat/Diubah

### Dibuat

1. `app/Http/Controllers/CashReferenceMonthController.php` - Controller baru
2. `resources/views/cash-reference-month-detail.blade.php` - View custom

### Diubah

1. `routes/web.php` - Tambah route untuk halaman custom
2. `app/Filament/Resources/CashReferenceResource/Pages/ViewCashReferenceMonthly.php` - Update link

### Tidak Digunakan Lagi

1. `app/Filament/Resources/CashReferenceResource/Pages/ViewCashReferenceMonthDetail.php` - Diganti dengan controller custom
2. `resources/views/filament/resources/cash-reference-resource/pages/view-cash-reference-month-detail.blade.php` - Diganti dengan blade custom

## Keuntungan Solusi Ini

1. ✅ **Tidak ada error month lagi** - Validasi parameter di controller
2. ✅ **CRUD lengkap** - Create, Edit, Delete semua berfungsi
3. ✅ **Delete berhasil** - Redirect dengan parameter yang benar
4. ✅ **Create via modal** - Tidak perlu pindah halaman, lebih cepat
5. ✅ **Save & Create Another** - Fitur seperti Filament untuk input data berulang
6. ✅ **AJAX submission** - Form submit tanpa reload halaman (kecuali tombol "Save")
7. ✅ **Validasi real-time** - Error ditampilkan di modal
8. ✅ **UI lebih baik** - Standalone HTML, tidak terbatas layout Filament
9. ✅ **Modal konfirmasi** - UX lebih baik saat delete
10. ✅ **Responsive** - Tailwind CSS memastikan tampilan mobile-friendly
11. ✅ **Flash messages** - Success/error messages setelah operasi

## Catatan

-   Halaman ini standalone dan tidak menggunakan Filament table component
-   **Create transaction** menggunakan modal dengan AJAX submission
-   **Delete action** menggunakan modal JavaScript untuk konfirmasi
-   Setelah delete, user di-redirect kembali ke halaman yang sama dengan parameter year/month yang sama
-   Validasi parameter dilakukan di controller untuk mencegah error Carbon
-   Field CoA diload dari database dan ditampilkan dalam dropdown
-   Default transaction_date diset ke hari ini saat modal dibuka
