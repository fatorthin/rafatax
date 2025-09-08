# Staff Attendance Resource untuk Panel App

## Overview

StaffAttendanceResource telah berhasil ditambahkan ke panel app (`/app`) dengan fitur yang sama seperti di panel admin. Resource ini memungkinkan user untuk mengelola data presensi karyawan dengan sistem permission yang terintegrasi.

## Fitur yang Tersedia

### 1. Form Input Presensi

-   **Staff Selection**: Dropdown untuk memilih karyawan
-   **Tanggal**: Date picker untuk tanggal presensi
-   **Status Kehadiran**:
    -   Masuk
    -   Sakit
    -   Izin
    -   Cuti
    -   Alfa
    -   Tengah Hari
-   **Jam Masuk & Pulang**: Time picker dengan format H:i
-   **Durasi Lembur**: Otomatis dihitung jika pulang setelah jam 17:30
-   **Checklist**:
    -   Terlambat
    -   Visit Solo
    -   Visit Luar Solo
-   **Keterangan**: Textarea untuk catatan tambahan

### 2. Table View

-   **Nama Karyawan**: Dengan search dan sort
-   **Tanggal**: Format Indonesia (hari, tanggal bulan tahun)
-   **Jam Masuk & Pulang**: Format H:i
-   **Durasi Lembur**: Dalam jam
-   **Status Kehadiran**: Badge dengan warna berbeda
-   **Terlambat**: Badge Ya/Tidak
-   **Visit Solo**: Badge Ya/Tidak
-   **Visit Luar Solo**: Badge Ya/Tidak
-   **Keterangan**: Text dengan wrap

### 3. Laporan Presensi Bulanan

-   **Filter Periode**: Modal untuk memilih bulan dan tahun
-   **View Khusus**: Halaman terpisah untuk laporan bulanan
-   **Notifikasi**: Konfirmasi saat periode berubah

### 4. Actions

-   **Create**: Membuat presensi baru
-   **Edit**: Mengedit presensi yang ada
-   **Delete**: Menghapus presensi
-   **Force Delete**: Hapus permanen
-   **Restore**: Restore dari soft delete
-   **Bulk Actions**: Operasi massal

## Permission System

### Permissions yang Tersedia

-   `staff-attendance.view`: Melihat data presensi
-   `staff-attendance.create`: Membuat data presensi
-   `staff-attendance.edit`: Mengedit data presensi
-   `staff-attendance.delete`: Menghapus data presensi

### Role yang Sudah Dibuat

-   **HRD Manager**: Memiliki semua permission untuk staff attendance
-   **Viewer**: Hanya bisa melihat data presensi

## Cara Penggunaan

### 1. Untuk Admin

1. Login ke panel admin (`/admin`)
2. Buka **System > Roles**
3. Edit role yang ingin diberi permission staff attendance
4. Pilih permissions yang sesuai
5. Save perubahan

### 2. Untuk User dengan Permission

1. Login ke panel app (`/app`)
2. Buka menu **HRD > Presensi Karyawan**
3. Gunakan fitur sesuai permission yang dimiliki

### 3. Membuat Presensi Baru

1. Klik tombol **Create**
2. Pilih staff dari dropdown
3. Pilih tanggal presensi
4. Pilih status kehadiran
5. Isi jam masuk dan pulang
6. Checklist keterlambatan/visit jika perlu
7. Tambahkan keterangan jika ada
8. Save

### 4. Melihat Laporan Bulanan

1. Klik tombol **Laporan Presensi Bulanan**
2. Klik **Pilih Periode** untuk mengubah bulan/tahun
3. Pilih periode yang diinginkan
4. Klik **Terapkan Filter**

## Konfigurasi Teknis

### File yang Dibuat/Diupdate

-   `app/Filament/App/Resources/StaffAttendanceResource.php`
-   `app/Filament/App/Resources/StaffAttendanceResource/Pages/ViewAttendanceMonthly.php`
-   `database/seeders/PermissionSeeder.php`: Menambahkan permission staff-attendance

### Routes yang Tersedia

-   `GET /app/staff-attendances`: List presensi
-   `GET /app/staff-attendances/create`: Form create
-   `GET /app/staff-attendances/{record}/edit`: Form edit
-   `GET /app/staff-attendances/monthly`: Laporan bulanan

### Model yang Digunakan

-   `App\Models\StaffAttendance`: Model utama
-   `App\Models\Staff`: Untuk dropdown staff

## Fitur Khusus

### 1. Auto Calculate Overtime

```php
// Otomatis menghitung durasi lembur jika pulang setelah 17:30
if ($jamPulang->greaterThan($batasLembur)) {
    $selisihMenit = $jamPulang->diffInMinutes($batasLembur);
    $durasiLembur = abs(round($selisihMenit / 60, 1));
}
```

### 2. Status Badge Colors

-   **Masuk**: Success (hijau)
-   **Sakit**: Primary (biru)
-   **Izin**: Warning (kuning)
-   **Cuti**: Info (biru muda)
-   **Alfa**: Danger (merah)
-   **Tengah Hari**: Warning (kuning)

### 3. Soft Delete Support

-   Resource mendukung soft delete
-   Filter untuk data yang dihapus
-   Action restore untuk mengembalikan data

## Testing

### 1. Test Permission

```bash
# Buat user dengan role hrd-manager
php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name' => 'HRD Manager',
    'email' => 'hrd@example.com',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
]);

$role = \App\Models\Role::where('name', 'hrd-manager')->first();
$user->roles()->attach($role->id);
```

### 2. Test Access

1. Login dengan user hrd-manager
2. Pastikan bisa akses menu **HRD > Presensi Karyawan**
3. Test semua fitur: create, edit, delete, view monthly

## Troubleshooting

### Permission tidak berfungsi

1. Pastikan user memiliki role yang benar
2. Pastikan role memiliki permission staff-attendance
3. Check apakah permission sudah di-seed ke database

### Form tidak muncul

1. Pastikan model StaffAttendance dan Staff sudah ada
2. Check apakah relasi antara model sudah benar
3. Pastikan migration sudah dijalankan

### Laporan bulanan tidak muncul

1. Pastikan view file sudah ada di `resources/views/filament/resources/staff-attendance-resource/pages/view-attendance-monthly.blade.php`
2. Check apakah route sudah terdaftar dengan benar

## Future Enhancements

1. **Export Data**: Tambahkan fitur export ke Excel/PDF
2. **Import Data**: Tambahkan fitur import presensi dari file
3. **Dashboard Widget**: Widget untuk statistik presensi
4. **Notification**: Notifikasi untuk keterlambatan
5. **API Integration**: Integrasi dengan sistem absensi eksternal
