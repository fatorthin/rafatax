<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // System Management (untuk panel app)
            ['name' => 'role.view', 'display_name' => 'View Roles', 'description' => 'Melihat daftar role', 'resource' => 'role', 'action' => 'view'],
            ['name' => 'role.create', 'display_name' => 'Create Role', 'description' => 'Membuat role baru', 'resource' => 'role', 'action' => 'create'],
            ['name' => 'role.edit', 'display_name' => 'Edit Role', 'description' => 'Mengedit data role', 'resource' => 'role', 'action' => 'edit'],
            ['name' => 'role.delete', 'display_name' => 'Delete Role', 'description' => 'Menghapus role', 'resource' => 'role', 'action' => 'delete'],

            // Permission Management (untuk panel app)
            ['name' => 'permission.view', 'display_name' => 'View Permissions', 'description' => 'Melihat daftar permission', 'resource' => 'permission', 'action' => 'view'],
            ['name' => 'permission.create', 'display_name' => 'Create Permission', 'description' => 'Membuat permission baru', 'resource' => 'permission', 'action' => 'create'],
            ['name' => 'permission.edit', 'display_name' => 'Edit Permission', 'description' => 'Mengedit data permission', 'resource' => 'permission', 'action' => 'edit'],
            ['name' => 'permission.delete', 'display_name' => 'Delete Permission', 'description' => 'Menghapus permission', 'resource' => 'permission', 'action' => 'delete'],

            // Cash Reference Management (untuk panel app)
            ['name' => 'cash-reference.view', 'display_name' => 'View Cash References', 'description' => 'Melihat daftar referensi kas', 'resource' => 'cash-reference', 'action' => 'view'],
            ['name' => 'cash-reference.create', 'display_name' => 'Create Cash Reference', 'description' => 'Membuat referensi kas baru', 'resource' => 'cash-reference', 'action' => 'create'],
            ['name' => 'cash-reference.edit', 'display_name' => 'Edit Cash Reference', 'description' => 'Mengedit data referensi kas', 'resource' => 'cash-reference', 'action' => 'edit'],
            ['name' => 'cash-reference.delete', 'display_name' => 'Delete Cash Reference', 'description' => 'Menghapus referensi kas', 'resource' => 'cash-reference', 'action' => 'delete'],

            // Cash Report Management (untuk panel app)
            ['name' => 'cash-report.view', 'display_name' => 'View Cash Reports', 'description' => 'Melihat laporan kas', 'resource' => 'cash-report', 'action' => 'view'],
            ['name' => 'cash-report.create', 'display_name' => 'Create Cash Report', 'description' => 'Membuat laporan kas', 'resource' => 'cash-report', 'action' => 'create'],
            ['name' => 'cash-report.edit', 'display_name' => 'Edit Cash Report', 'description' => 'Mengedit laporan kas', 'resource' => 'cash-report', 'action' => 'edit'],
            ['name' => 'cash-report.delete', 'display_name' => 'Delete Cash Report', 'description' => 'Menghapus laporan kas', 'resource' => 'cash-report', 'action' => 'delete'],

            // MoU Management (untuk panel app)
            ['name' => 'mou.view', 'display_name' => 'View MoUs', 'description' => 'Melihat daftar MoU', 'resource' => 'mou', 'action' => 'view'],
            ['name' => 'mou.create', 'display_name' => 'Create MoU', 'description' => 'Membuat MoU baru', 'resource' => 'mou', 'action' => 'create'],
            ['name' => 'mou.edit', 'display_name' => 'Edit MoU', 'description' => 'Mengedit data MoU', 'resource' => 'mou', 'action' => 'edit'],
            ['name' => 'mou.delete', 'display_name' => 'Delete MoU', 'description' => 'Menghapus MoU', 'resource' => 'mou', 'action' => 'delete'],

            // Staff Attendance Management (untuk panel app)
            ['name' => 'staff-attendance.view', 'display_name' => 'View Staff Attendance', 'description' => 'Melihat data presensi karyawan', 'resource' => 'staff-attendance', 'action' => 'view'],
            ['name' => 'staff-attendance.create', 'display_name' => 'Create Staff Attendance', 'description' => 'Membuat data presensi karyawan', 'resource' => 'staff-attendance', 'action' => 'create'],
            ['name' => 'staff-attendance.edit', 'display_name' => 'Edit Staff Attendance', 'description' => 'Mengedit data presensi karyawan', 'resource' => 'staff-attendance', 'action' => 'edit'],
            ['name' => 'staff-attendance.delete', 'display_name' => 'Delete Staff Attendance', 'description' => 'Menghapus data presensi karyawan', 'resource' => 'staff-attendance', 'action' => 'delete'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}