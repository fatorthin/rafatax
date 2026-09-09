<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class MouTemplate extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'mou_templates';

    protected $fillable = [
        'name',
        'type',
        'description',
        'sections',
    ];

    protected $casts = [
        'sections' => 'array',
    ];

    public function mous()
    {
        return $this->hasMany(MoU::class, 'mou_template_id');
    }

    /**
     * Default presets jika user ingin menginisialisasi template standar.
     */
    public static function getDefaultSections(): array
    {
        return [
            [
                'title' => 'Tujuan dan Ruang Lingkup',
                'content' => '<p>Tujuan perikatan jasa supervisi kewajiban perpajakan ini adalah, Pihak Kedua dapat membantu Pihak Pertama dalam supervisi dan penyusunan kewajiban perpajakan sesuai dengan ketentuan perundang-undangan perpajakan yang berlaku di Indonesia.</p>',
                'include_cost_table' => false,
            ],
            [
                'title' => 'Tanggung Jawab dan Kewajiban Para Pihak',
                'content' => '<p><strong>Pihak Pertama berkewajiban:</strong></p><ol><li>Menyediakan data, dokumen, bukti transaksi, dan informasi yang benar, lengkap, dan sah yang diperlukan oleh Pihak Kedua.</li><li>Menunjuk person in charge (PIC) yang berwenang untuk berkoordinasi dengan Pihak Kedua.</li><li>Membayar imbalan jasa sesuai dengan ketentuan dan termin yang disepakati.</li></ol><p><strong>Pihak Kedua berkewajiban:</strong></p><ol><li>Melaksanakan ruang lingkup pekerjaan secara profesional, cermat, dan sesuai standar praktik perpajakan.</li><li>Menjaga kerahasiaan seluruh data dan dokumen milik Pihak Pertama.</li><li>Memberikan konsultasi dan arahan perpajakan selama periode kontrak kerja sama.</li></ol>',
                'include_cost_table' => false,
            ],
            [
                'title' => 'Jangka Waktu Pengerjaan',
                'content' => '<p>Perikatan kerja sama ini berlaku efektif sejak tanggal <strong>{TANGGAL_AWAL}</strong> sampai dengan <strong>{TANGGAL_AKHIR}</strong>. Perpanjangan masa kerja sama dapat dilakukan atas kesepakatan tertulis kedua belah pihak.</p>',
                'include_cost_table' => false,
            ],
            [
                'title' => 'Biaya Jasa dan Cara Penagihan',
                'content' => '<p>Atas pekerjaan yang dilakukan oleh Pihak Kedua, Pihak Pertama setuju untuk membayar imbalan jasa konsultasi sesuai dengan rincian biaya dan termin penagihan di bawah ini:</p>',
                'include_cost_table' => true,
            ],
            [
                'title' => 'Kerahasiaan Dokumen',
                'content' => '<p>Pihak Kedua wajib menjaga kerahasiaan semua data, catatan, dan informasi yang diperoleh dari Pihak Pertama, baik selama berlakunya perikatan ini maupun setelah berakhirnya kerja sama, kecuali diwajibkan oleh undang-undang atau aparat penegak hukum.</p>',
                'include_cost_table' => false,
            ],
            [
                'title' => 'Penyelesaian Perselisihan',
                'content' => '<p>Segala perselisihan yang timbul dari pelaksanaan perjanjian ini akan diselesaikan secara musyawarah untuk mufakat. Apabila kesepakatan tidak tercapai, para pihak sepakat untuk menyelesaikannya melalui Pengadilan Negeri yang berwenang.</p>',
                'include_cost_table' => false,
            ],
        ];
    }
}
