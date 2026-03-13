<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BeritaAcara extends Model
{
    protected $table = 'berita_acaras';

    protected $fillable = [
        'title',
        'number',
        'document_number',
        'letter_date',
        'event_date',
        'event_name',
        'event_location',
        'start_time',
        'end_time',
        'duration_hours',
        'attendees',
        'results',
        'customer_id',
        'department_id',
        'vehicle_no',
        'incident_time',
        'chronology',
        'pdf_path',
        'ba_pdf_template',
        'pdf_template_fingerprint',
        'pdf_generated_at',
        'created_by',
    ];

    protected $casts = [
        'letter_date' => 'date',
        'event_date' => 'date',
        'attendees' => 'array',
        'results' => 'array',
        'duration_hours' => 'float',
        'pdf_generated_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public static function generateNumber(Carbon $letterDate): string
    {
        $prefix = 'BA-' . $letterDate->format('Y-m') . '-';

        $last = static::query()
            ->where('number', 'like', $prefix . '%')
            ->orderByDesc('number')
            ->value('number');

        $seq = 1;
        if (is_string($last) && preg_match('/^' . preg_quote($prefix, '/') . '(\\d{4})$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function generateDocumentNumber(Carbon $createdAt): string
    {
        $prefix = 'DOC-' . $createdAt->format('Y-m') . '-';

        $last = static::query()
            ->whereNotNull('document_number')
            ->where('document_number', 'like', $prefix . '%')
            ->orderByDesc('document_number')
            ->value('document_number');

        $seq = 1;
        if (is_string($last) && preg_match('/^' . preg_quote($prefix, '/') . '(\\d{4})$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
