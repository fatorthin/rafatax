# Cara Penggunaan Sistem Permission untuk Panel App

## Overview

Sistem permission ini memungkinkan admin untuk mengatur hak akses user ke resource di panel app (`/app`). User dengan role selain admin akan dibatasi aksesnya berdasarkan permission yang diberikan.

## Langkah-langkah Penggunaan

### 1. Login sebagai Admin

1. Akses panel admin di `/admin`
2. Login dengan akun yang memiliki role `admin`
3. Admin akan memiliki akses penuh ke semua resource

### 2. Mengelola Permissions

1. Buka menu **System** > **Permissions**
2. Di sini Anda dapat:
    - Melihat daftar permission yang tersedia
    - Membuat permission baru jika diperlukan
    - Mengedit permission yang sudah ada
    - Menghapus permission yang tidak diperlukan

### 3. Mengelola Roles

1. Buka menu **System** > **Roles**
2. Di sini Anda dapat:
    - Melihat daftar role yang tersedia
    - Membuat role baru
    - Mengedit role yang sudah ada
    - Menentukan permission apa saja yang diberikan ke role tersebut

### 4. Mengatur User dengan Role

1. Buka menu **System** > **Users**
2. Edit user yang ingin diatur hak aksesnya
3. Pilih role yang sesuai untuk user tersebut

## Contoh Role yang Sudah Dibuat

### 1. Admin

-   **Deskripsi**: Memiliki akses penuh ke semua resource
-   **Permission**: Semua permission (otomatis)

### 2. Cash Manager

-   **Deskripsi**: Manager yang dapat mengelola cash reference dan cash report
-   **Permission**:
    -   `cash-reference.view`
    -   `cash-reference.create`
    -   `cash-reference.edit`
    -   `cash-report.view`
    -   `cash-report.create`
    -   `cash-report.edit`

### 3. MoU Manager

-   **Deskripsi**: Manager yang dapat mengelola MoU
-   **Permission**:
    -   `mou.view`
    -   `mou.create`
    -   `mou.edit`

### 4. HRD Manager

-   **Deskripsi**: Manager HRD yang dapat mengelola presensi karyawan
-   **Permission**:
    -   `staff-attendance.view`
    -   `staff-attendance.create`
    -   `staff-attendance.edit`
    -   `staff-attendance.delete`

### 5. Viewer

-   **Deskripsi**: User yang hanya dapat melihat data
-   **Permission**:
    -   `cash-reference.view`
    -   `cash-report.view`
    -   `mou.view`
    -   `staff-attendance.view`

## Testing Sistem Permission

### 1. Buat User Baru

```bash
php artisan tinker
```

```php
// Buat user baru
$user = \App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
]);

// Assign role cash-manager
$role = \App\Models\Role::where('name', 'cash-manager')->first();
$user->roles()->attach($role->id);

echo 'User berhasil dibuat dengan role cash-manager';
```

### 2. Test Login

1. Login dengan user yang baru dibuat
2. User tersebut hanya akan melihat menu yang sesuai dengan permission-nya
3. User tidak akan bisa mengakses resource yang tidak memiliki permission

## Menambahkan Permission Baru

### 1. Tambahkan ke Seeder

Edit file `database/seeders/PermissionSeeder.php`:

```php
// Tambahkan permission baru
['name' => 'new-resource.view', 'display_name' => 'View New Resource', 'description' => 'Melihat resource baru', 'resource' => 'new-resource', 'action' => 'view'],
```

### 2. Jalankan Seeder

```bash
php artisan db:seed --class=PermissionSeeder
```

### 3. Update Resource

Gunakan trait `HasPermissions` di resource Filament:

```php
use App\Traits\HasPermissions;

class YourResource extends Resource
{
    use HasPermissions;
    // ... rest of your code
}
```

## Troubleshooting

### User tidak bisa mengakses resource

1. Pastikan user memiliki role yang benar
2. Pastikan role memiliki permission yang diperlukan
3. Check apakah permission sudah ada di database
4. Pastikan resource menggunakan trait `HasPermissions`

### Permission tidak muncul di role form

1. Pastikan permission sudah di-seed ke database
2. Check apakah permission memiliki field yang benar
3. Pastikan relasi antara Role dan Permission sudah benar

### Error saat mengakses panel app

1. Pastikan user sudah login
2. Check apakah user memiliki minimal satu role
3. Pastikan role memiliki minimal satu permission

## Struktur Database

### Tabel `permissions`

-   `id`: Primary key
-   `name`: Nama permission (format: resource.action)
-   `display_name`: Nama yang ditampilkan
-   `description`: Deskripsi permission
-   `resource`: Nama resource
-   `action`: Action yang diizinkan
-   `created_at`, `updated_at`: Timestamps

### Tabel `role_permissions`

-   `id`: Primary key
-   `role_id`: Foreign key ke tabel roles
-   `permission_id`: Foreign key ke tabel permissions
-   `created_at`, `updated_at`: Timestamps

### Tabel `role_user`

-   `id`: Primary key
-   `role_id`: Foreign key ke tabel roles
-   `user_id`: Foreign key ke tabel users
-   `created_at`, `updated_at`: Timestamps

## Best Practices

1. **Gunakan naming convention yang konsisten**: `resource.action` (e.g., `cash-reference.view`)
2. **Buat role yang spesifik**: Hindari role yang terlalu umum
3. **Test permission secara berkala**: Pastikan permission berfungsi dengan benar
4. **Dokumentasikan role dan permission**: Buat dokumentasi untuk tim
5. **Gunakan principle of least privilege**: Berikan permission minimal yang diperlukan

## Contoh Penggunaan di Code

### Check Permission di Controller

```php
public function index()
{
    if (!auth()->user()->hasPermission('cash-reference.view')) {
        abort(403, 'Unauthorized');
    }

    // Your code here
}
```

### Check Permission di Blade Template

```blade
@if(auth()->user()->hasPermission('cash-reference.create'))
    <a href="{{ route('filament.app.resources.cash-references.create') }}" class="btn btn-primary">
        Create Cash Reference
    </a>
@endif
```

### Check Multiple Permissions

```php
if (auth()->user()->hasAnyPermission(['cash-reference.create', 'cash-reference.edit'])) {
    // User dapat membuat atau mengedit cash reference
}
```
