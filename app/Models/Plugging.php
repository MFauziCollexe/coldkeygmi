<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plugging extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'customer',
        'no_container_no_polisi',
        'suhu_awal',
        'suhu_akhir',
        'lokasi',
        'user_id',
        'status',
        'transporter',
        'nomor_dokumen',
        'rencana_waktu_kedatangan',
        'jumlah_kendaraan',
        'jenis_kendaraan',
        'pintu_loading',
        'keterangan',
        'pic_customer',
        'signature_image',
        'durasi_menit',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'suhu_awal' => 'decimal:2',
        'suhu_akhir' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
