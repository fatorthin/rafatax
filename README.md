# Rafatax Project

Project Rafatax Management System built with Laravel 12 & Filament 3.3.

## 📚 Knowledge Base & Documentation

### External Documentation (Tech Stack)

-   **Framework**: [Laravel 12 Documentation](https://laravel.com/docs/master)
-   **Admin Panel**: [FilamentPHP v3 Documentation](https://filamentphp.com/docs/3.x)
-   **Livewire**: [Livewire v3 Documentation](https://livewire.laravel.com/docs)
-   **WhatsApp Service**: [Wablas API Documentation](https://texas.wablas.com/documentation/api)
-   **PDF Generation**: [Laravel DomPDF](https://github.com/barryvdh/laravel-dompdf)
-   **Excel/Spreadsheet**: [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/en/latest/)

### Internal Project Documentation

#### System Guides

-   🔐 [Permission System Guide](PERMISSION_SYSTEM.md) - Panduan sistem permission custom untuk App Panel.
-   💬 [Wablas Integration Fix](WABLAS_FIX_DOCUMENTATION.md) - Dokumentasi perbaikan dan standar integerasi WhatsApp.
-   📱 [PDF & Troubleshooting](WABLAS_PDF_TROUBLESHOOTING.md) - Solusi masalah PDF dan pengiriman WA.

#### API Documentation

-   👥 [Staff API Docs](STAFF_API_DOCUMENTATION.md) - Dokumentasi lengkap endpoint Staff.
-   🏢 [Client API Docs](CLIENT_API_DOCUMENTATION.md) - Dokumentasi endpoint Client.
-   🚀 [Quick Start Client API](CLIENT_API_QUICK_REFERENCE.md) - Panduan cepat integrasi Client API.

## 🛠 Project Structure Overview

-   **App Panel**: Custom frontend panel for users (`/app`).
-   **Admin Panel**: Filament-based admin interface (`/admin`).
-   **Services**:
    -   `WablasService`: Handles WhatsApp message & document sending with fallback mechanisms.

## 🚀 Quick Commands

```bash
# Start Development Server
composer run dev

# Clear Cache
php artisan optimize:clear

# Run Tests
php artisan test
```
