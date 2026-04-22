# Modul Compress PDF - Setup & Documentation

## Deskripsi

Modul **Compress PDF** memungkinkan user untuk mengupload dan mengompresi file PDF dengan tiga tingkat kompresi: Low, Medium, dan High. Modul ini mendukung batch compress multiple PDFs dan menyimpan file terkompresi di server.

## Lokasi Modul

- **Menu**: GMISL > Tools > Compress PDF
- **Route**: `/gmisl/tools/compress-pdf`

## Requirements

- **GhostScript** harus terinstall di server (untuk compression engine)
    - Linux: `apt-get install ghostscript`
    - Windows: Download dari https://www.ghostscriptplus.com/
    - macOS: `brew install ghostscript`

## Setup Instructions

### 1. Database Migration

Jalankan migration untuk membuat table compress_pdf_jobs:

```bash
php artisan migrate
```

### 2. Create Storage Directories

Buat folder untuk menyimpan file:

```bash
mkdir -p storage/app/pdf-uploads
mkdir -p storage/app/compressed-pdfs
mkdir -p storage/app/temp-pdf-compress
chmod -R 755 storage/app/pdf-uploads
chmod -R 755 storage/app/compressed-pdfs
chmod -R 755 storage/app/temp-pdf-compress
```

### 3. Grant Module Permission

Pastikan user yang ingin mengakses modul ini memiliki permission `tools.compress_pdf` di Module Control.

### 4. Verify GhostScript Installation

Test apakah GhostScript terinstall:

```bash
gs --version
```

## Fitur Utama

### 1. Upload & Single Compress

- User bisa upload 1 file PDF
- Pilih compression level (Low, Medium, High)
- File akan dikompresi dan disimpan di server

### 2. Batch Compress

- User bisa upload multiple PDF files sekaligus
- Semua file dikompresi dengan level yang sama
- Setiap file memiliki job record terpisah

### 3. Compression Levels

| Level      | Quality    | File Size | Best For                                 |
| ---------- | ---------- | --------- | ---------------------------------------- |
| **Low**    | Best       | Large     | Important documents, high-quality images |
| **Medium** | Good       | Medium    | General use, email, sharing              |
| **High**   | Acceptable | Small     | Text docs, internal reports              |

### 4. Job Management

- View semua compression jobs dalam tabel history
- Download file yang sudah dikompres
- Delete job untuk membebaskan storage
- Lihat stats: original size, compressed size, compression ratio

### 5. Statistics Dashboard

- Total jobs
- Completed jobs count
- Processing jobs count
- Failed jobs count
- Average compression ratio

## Database Schema

### compress_pdf_jobs Table

```sql
CREATE TABLE compress_pdf_jobs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    compressed_filename VARCHAR(255),
    original_path VARCHAR(255) NOT NULL,
    compressed_path VARCHAR(255),
    original_size BIGINT DEFAULT 0,
    compressed_size BIGINT,
    compression_ratio DECIMAL(5,2),
    compression_level ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (status),
    INDEX (created_at)
);
```

## API Endpoints

### Index - List all jobs

```
GET /gmisl/tools/compress-pdf
```

### Upload & Compress Single/Batch

```
POST /gmisl/tools/compress-pdf/upload
Body: FormData
- files[] (multiple PDF files)
- compression_level (low|medium|high)
```

### Download Compressed File

```
GET /gmisl/tools/compress-pdf/{jobId}/download
```

### Get Job Details

```
GET /gmisl/tools/compress-pdf/{jobId}
```

### Delete Job

```
DELETE /gmisl/tools/compress-pdf/{jobId}
```

### Get Statistics

```
GET /gmisl/tools/compress-pdf-stats
```

## File Structure

```
coldkeygmi/
├── app/
│   ├── Models/
│   │   └── CompressPdfJob.php
│   ├── Http/Controllers/
│   │   └── CompressPdfController.php
│   └── Services/
│       └── PdfCompressionService.php
├── database/
│   └── migrations/
│       └── 2026_04_22_create_compress_pdf_jobs_table.php
├── resources/js/
│   └── Pages/Tools/CompressPdf/
│       └── Index.vue
├── routes/
│   └── web.php (with compress-pdf routes)
├── storage/app/
│   ├── pdf-uploads/
│   ├── compressed-pdfs/
│   └── temp-pdf-compress/
└── config/
    └── modules.php (with tools.compress_pdf menu)
```

## Usage Example

### From Frontend (Vue)

```javascript
// Upload files
const formData = new FormData();
formData.append("files[]", file1);
formData.append("files[]", file2);
formData.append("compression_level", "medium");

const response = await axios.post("/gmisl/tools/compress-pdf/upload", formData);

// Download file
window.location.href = `/gmisl/tools/compress-pdf/${jobId}/download`;
```

### From Backend (PHP)

```php
use App\Services\PdfCompressionService;
use App\Models\CompressPdfJob;

$service = app(PdfCompressionService::class);
$job = CompressPdfJob::find($jobId);
return $service->downloadCompressed($job);
```

## Cleanup Old Files

To cleanup old compressed files (older than 7 days):

```bash
php artisan tinker
>>> app(App\Services\PdfCompressionService::class)->cleanupOldFiles(7)
```

Or add to scheduler in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        app(App\Services\PdfCompressionService::class)->cleanupOldFiles(7);
    })->daily();
}
```

## Troubleshooting

### GhostScript Not Found

- Install GhostScript on your server
- Verify path: `which gs` (Linux) or check PATH (Windows)
- Update PdfCompressionService if GhostScript is in non-standard path

### File Permission Issues

```bash
chmod -R 755 storage/app/pdf-uploads
chmod -R 755 storage/app/compressed-pdfs
chmod -R 755 storage/app/temp-pdf-compress
```

### Database Migration Error

```bash
php artisan migrate:refresh
# or
php artisan migrate --path=database/migrations/2026_04_22_create_compress_pdf_jobs_table.php
```

### Storage Disk Issues

Verify config/filesystems.php has proper disk configuration:

```php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
        'permissions' => [
            'file' => 0644,
            'dir' => 0755,
        ],
    ],
]
```

## Performance Notes

- Max file size per upload: 100MB (configurable)
- Compression level directly impacts processing time
- High compression takes longer but produces smaller files
- Consider using queue for large batch operations

## Security Considerations

- Only authenticated users can access this module
- Users can only see/download their own jobs
- Admin users can see all jobs
- File validation ensures only PDF files are processed
- Temporary files are cleaned up after compression

## Future Enhancements

- [ ] Queue-based async compression for large files
- [ ] Email notification when compression completes
- [ ] Support for multiple file formats (DOCX, XLSX, etc.)
- [ ] Advanced compression settings per file
- [ ] Integration with cloud storage (S3, GCS)
- [ ] Compression history export
- [ ] API token authentication for external use
