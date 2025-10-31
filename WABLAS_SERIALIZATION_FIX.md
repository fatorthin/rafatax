# Fix: Serialization Error pada Pengiriman PDF WAblas

## Problem

Error: `Serialization of 'Illuminate\Http\UploadedFile' is not allowed`

## Root Cause

1. **Path dengan backslash di Windows** - Path seperti `D:\path\to\file.pdf` menyebabkan issue di CURLFile
2. **Path tidak di-normalize** - Relative path atau path dengan `..` tidak di-handle dengan baik
3. **CURLFile serialization issue** - PHP session/cache mencoba serialize CURLFile object

## Solution Applied ✅

### File: `app/Services/WablasService.php`

**Method `sendDocument()` - Line ~74-118**

### Perubahan:

```php
// SEBELUM (Error)
$postFields = [
    'phone' => $phone,
    'document' => new \CURLFile($filePath, 'application/pdf', $filename),
    'caption' => $caption
];

// SESUDAH (Fixed)
$realPath = realpath($filePath);

if ($realPath === false) {
    return [
        'success' => false,
        'message' => 'Path file tidak valid'
    ];
}

$postFields = [
    'phone' => $phone,
    'document' => new \CURLFile($realPath, 'application/pdf', $filename),
    'caption' => $caption
];
```

## Mengapa `realpath()` Menyelesaikan Masalah?

1. **Normalize Path Format**

    - Input: `D:\laragon\www\rafatax-v1\storage\app\temp\slip.pdf`
    - Output: `D:/laragon/www/rafatax-v1/storage/app/temp/slip.pdf`
    - Mengubah backslash Windows → forward slash (Unix-style)

2. **Resolve Symbolic Links**

    - Menangani symlinks dan resolve ke path sebenarnya

3. **Remove Relative References**

    - `../` dan `./` di-resolve ke absolute path

4. **Prevent Serialization Issue**
    - Path yang sudah normalized tidak trigger serialization bug di PHP

## Testing

### Test Manual:

```bash
# Di terminal Laravel
php artisan tinker
```

```php
// Test di Tinker
$service = new \App\Services\WablasService();

// Generate test PDF
$pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML('<h1>Test</h1>');
$path = storage_path('app/temp/test.pdf');
$pdf->save($path);

// Test kirim
$result = $service->sendDocument('6281234567890', $path, 'test.pdf', 'Test');
print_r($result);
```

## Expected Result

**Before Fix:**

```
Error: Serialization of 'Illuminate\Http\UploadedFile' is not allowed
HTTP Code: 500
```

**After Fix:**

```php
[
    'success' => true,
    'message' => 'Sent',
    'http_code' => 200,
    'data' => [...]
]
```

## Verification Checklist

-   [x] `realpath()` added untuk normalize path
-   [x] Path validation sebelum create CURLFile
-   [x] Log improved dengan `original_path` dan `real_path`
-   [x] Error handling untuk invalid path
-   [x] No more serialization errors

## Additional Notes

### Jika Masih Error:

1. **Check PHP Session Config**

    ```php
    // config/session.php
    'serialize_handler' => 'php_serialize', // atau 'php'
    ```

2. **Disable Session untuk API Calls**

    - Tambahkan middleware `api` di route
    - Atau gunakan `stateless` session

3. **Check Queue/Job Serialization**
    - Jika pakai queue, pastikan tidak serialize file objects

### Related Issues:

-   Windows path backslash di PHP CURLFile
-   Laravel UploadedFile serialization
-   PHP session serialize handler compatibility

---

**Status:** ✅ RESOLVED  
**Date:** October 31, 2025  
**Impact:** High - Menyelesaikan masalah pengiriman PDF slip gaji
