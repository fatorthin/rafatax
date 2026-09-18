<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('case_project_power_of_attorneys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_project_id')->unique()->constrained('case_projects')->onDelete('cascade');
            
            // Surat Kuasa Khusus fields
            $table->string('nomor_surat_kuasa')->nullable();
            $table->date('tanggal_surat_kuasa')->nullable();
            $table->string('jenis_wp')->default('BADAN'); // BADAN, ORANG PRIBADI, WARISAN YANG BELUM TERBAGI
            $table->string('pemberi_nama')->nullable();
            $table->string('pemberi_npwp')->nullable();
            $table->string('pemberi_jabatan')->nullable();
            $table->string('bertindak_selaku')->default('wakil_wajib_pajak'); // wajib_pajak, wakil_wajib_pajak
            $table->string('wp_badan_nama')->nullable();
            $table->string('wp_badan_npwp')->nullable();
            $table->string('kuasa_kategori')->default('konsultan_pajak'); // konsultan_pajak, pihak_lain, keluarga
            $table->string('penerima_nama')->nullable();
            $table->string('penerima_npwp')->nullable();
            $table->string('penerima_izin_no')->nullable();
            $table->string('penerima_status_keluarga')->nullable();
            $table->text('kewajiban_terkait')->nullable();
            $table->text('jenis_pajak')->nullable();
            $table->string('masa_pajak_pilihan')->default('Tahun Pajak'); // Masa Pajak, Bagian Tahun Pajak, Tahun Pajak
            $table->string('masa_tahun_pajak')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->json('rincian_kuasa')->nullable();

            // Surat Pernyataan fields
            $table->string('pernyataan_nomor')->nullable();
            $table->date('pernyataan_tanggal')->nullable();
            $table->text('pernyataan_pemberi_alamat')->nullable();
            $table->text('pernyataan_penerima_alamat')->nullable();
            $table->text('pernyataan_status_hubungan')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_project_power_of_attorneys');
    }
};
