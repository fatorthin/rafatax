<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseProjectPowerOfAttorney extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'case_project_power_of_attorneys';

    protected $fillable = [
        'case_project_id',
        'nomor_surat_kuasa',
        'tanggal_surat_kuasa',
        'jenis_wp',
        'pemberi_nama',
        'pemberi_npwp',
        'pemberi_jabatan',
        'bertindak_selaku',
        'wp_badan_nama',
        'wp_badan_npwp',
        'kuasa_kategori',
        'penerima_nama',
        'penerima_npwp',
        'penerima_izin_no',
        'penerima_status_keluarga',
        'kewajiban_terkait',
        'jenis_pajak',
        'masa_pajak_pilihan',
        'masa_tahun_pajak',
        'tanggal_mulai',
        'tanggal_selesai',
        'rincian_kuasa',
        'pernyataan_nomor',
        'pernyataan_tanggal',
        'pernyataan_pemberi_alamat',
        'pernyataan_penerima_alamat',
        'pernyataan_status_hubungan',
    ];

    protected $casts = [
        'tanggal_surat_kuasa' => 'date',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'pernyataan_tanggal' => 'date',
        'rincian_kuasa' => 'array',
    ];

    public function caseProject()
    {
        return $this->belongsTo(CaseProject::class, 'case_project_id');
    }

    public static function getDefaultRincianKuasa(): array
    {
        return [
            ['text' => 'menghadap dan melakukan pembahasan dengan pejabat atau pegawai Direktorat Jenderal Pajak;'],
            ['text' => 'menyampaikan klarifikasi, tanggapan, jawaban baik secara lisan maupun tertulis, atau dokumen kepada pejabat atau pegawai Direktorat Jenderal Pajak;'],
            ['text' => 'menandatangani Surat Pemberitahuan Tahunan atau Surat Pemberitahuan Masa;'],
            ['text' => 'menandatangani surat-surat, formulir-formulir, atau dokumen perpajakan lainnya yang diperlukan dalam pelaksanaan hak dan/atau pemenuhan kewajiban perpajakan yang dikuasakan kepadanya;'],
        ];
    }
}
