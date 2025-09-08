# Sistem Permission Management untuk Panel App

Sistem ini memungkinkan admin untuk mengatur hak akses user ke berbagai resource di **panel app** (bukan panel admin). Panel app adalah panel yang digunakan oleh user biasa untuk mengakses fitur-fitur aplikasi seperti Cash Reference, Cash Report, dan MoU.

## Komponen Sistem

### 1. Model Permission

-   **File**: `app/Models/Permission.php`
-   **Fungsi**: Menyimpan daftar permission yang tersedia
-   **Field**:
    -   `name`: Format `resource.action` (e.g., `user.view`, `invoice.create`)
    -   `display_name`: Nama yang ditampilkan (e.g., "View Users", "Create Invoice")
    -   `description`: Deskripsi permission
    -   `resource`: Nama resource (e.g., `user`, `invoice`, `staff`)
    -   `action`: Action yang diizinkan (e.g., `view`, `create`, `edit`, `delete`)

### 2. Model RolePermission

-   **File**: `app/Models/RolePermission.php`
-   **Fungsi**: Relasi many-to-many antara Role dan Permission

### 3. Model Role (Updated)

-   **File**: `app/Models/Role.php`
-   **Fungsi**: Menambahkan relasi dengan Permission dan method untuk check permission

### 4. Model User (Updated)

-   **File**: `app/Models/User.php`
-   **Fungsi**: Menambahkan method untuk check permission melalui role

## Cara Penggunaan

### 1. Mengakses Panel Permission

1. Login sebagai admin
2. Buka menu **System** > **Permissions**
3. Kelola permission yang tersedia

### 2. Mengatur Permission untuk Role

1. Buka menu **System** > **Roles**
2. Edit role yang diinginkan
3. Pilih permissions yang akan diberikan ke role tersebut

### 3. Menggunakan Permission di Resource Filament

#### Cara 1: Menggunakan Trait HasPermissions

```php
use App\Traits\HasPermissions;

class YourResource extends Resource
{
    use HasPermissions;

    // Trait akan otomatis mengatur canAccess() berdasarkan permission
}
```

#### Cara 2: Manual Implementation

```php
public static function canAccess(): bool
{
    $user = auth()->user();

    if (!$user) {
        return false;
    }

    // Admin selalu memiliki akses
    if ($user->hasRole('admin')) {
        return true;
    }

    // Check permission
    return $user->hasPermission('your-resource.view');
}
```

### 4. Check Permission di Controller atau Logic Lainnya

```php
// Check single permission
if (auth()->user()->hasPermission('invoice.create')) {
    // User dapat membuat invoice
}

// Check multiple permissions
if (auth()->user()->hasAnyPermission(['invoice.create', 'invoice.edit'])) {
    // User dapat membuat atau mengedit invoice
}

// Get all permissions user
$permissions = auth()->user()->getAllPermissions();
```

## Permission yang Tersedia untuk Panel App

Sistem ini sudah menyediakan permission untuk resource yang ada di panel app:

### System Management

-   `role.view`, `role.create`, `role.edit`, `role.delete`
-   `permission.view`, `permission.create`, `permission.edit`, `permission.delete`

### Cash Reference Management

-   `cash-reference.view`, `cash-reference.create`, `cash-reference.edit`, `cash-reference.delete`

### Cash Report Management

-   `cash-report.view`, `cash-report.create`, `cash-report.edit`, `cash-report.delete`

### MoU Management

-   `mou.view`, `mou.create`, `mou.edit`, `mou.delete`

### Staff Attendance Management

-   `staff-attendance.view`, `staff-attendance.create`, `staff-attendance.edit`, `staff-attendance.delete`

## Menambahkan Permission Baru

### 1. Tambahkan ke Seeder

Edit file `database/seeders/PermissionSeeder.php` dan tambahkan permission baru:

```php
['name' => 'new-resource.view', 'display_name' => 'View New Resource', 'description' => 'Melihat resource baru', 'resource' => 'new-resource', 'action' => 'view'],
```

### 2. Jalankan Seeder

```bash
php artisan db:seed --class=PermissionSeeder
```

### 3. Update Resource

Gunakan trait `HasPermissions` atau implementasi manual di resource Filament.

## Migration & Seeder

### Migration

-   `2025_09_08_041454_create_permissions_table.php`
-   `2025_09_08_041459_create_role_permissions_table.php`

### Seeder

-   `PermissionSeeder.php`: Mengisi data permission awal

## Keamanan

1. **Admin Role**: User dengan role `admin` selalu memiliki akses penuh ke semua resource
2. **Permission Check**: Setiap resource akan mengecek permission sebelum memberikan akses
3. **Database Constraints**: Foreign key constraints memastikan integritas data
4. **Unique Constraints**: Mencegah duplikasi permission dan role-permission assignment

## Troubleshooting

### Permission tidak berfungsi

1. Pastikan user memiliki role yang benar
2. Pastikan role memiliki permission yang diperlukan
3. Check apakah permission sudah ada di database
4. Pastikan resource menggunakan method `canAccess()` yang benar

### Error saat migration

1. Pastikan tidak ada conflict dengan migration lain
2. Check foreign key constraints
3. Pastikan tabel `roles` sudah ada sebelum menjalankan migration `role_permissions`
