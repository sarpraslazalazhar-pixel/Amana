<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsetImportItem extends Model
{
    use HasFactory;

    protected $table = 'aset_import_items';

    protected $fillable = [
        'batch_id',
        'baris_ke',
        'kode_aset_lama',
        'kode_sistem_lama',
        'nama_aset_mentah',
        'kategori_mentah',
        'merk_mentah',
        'tipe_model',
        'produsen',
        'no_seri',
        'tahun_produksi',
        'lokasi_mentah',
        'pj_mentah',
        'deskripsi_mentah',
        'tanggal_pembelian',
        'toko_distributor',
        'no_invoice',
        'jumlah_unit',
        'harga_satuan',
        'umur_ekonomis_tahun',
        'nilai_residu',
        'keterangan_tambahan',
        'kategori_id',
        'barang_id',
        'sifat_barang',
        'lokasi_id',
        'penanggung_jawab_id',
        'divisi_id',
        'cara_perolehan',
        'status_barang',
        'is_duplicate',
        'existing_aset_id',
        'missing_components',
        'is_ready',
        'generated_kode_aset',
        'import_status',
        'error_message',
    ];

    protected $casts = [
        'tanggal_pembelian' => 'date',
        'harga_satuan' => 'decimal:2',
        'nilai_residu' => 'decimal:2',
        'is_duplicate' => 'boolean',
        'is_ready' => 'boolean',
        'missing_components' => 'array',
        'jumlah_unit' => 'integer',
        'umur_ekonomis_tahun' => 'integer',
    ];

    public function batch()
    {
        return $this->belongsTo(AsetImportBatch::class, 'batch_id');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function penanggungJawab()
    {
        return $this->belongsTo(PenanggungJawab::class, 'penanggung_jawab_id');
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    public function existingAset()
    {
        return $this->belongsTo(Aset::class, 'existing_aset_id');
    }

    /**
     * Cek dan evaluasi kelengkapan 9 komponen pembentuk kode aset.
     */
    public function evaluateReadiness(): bool
    {
        $missing = [];

        if (! $this->kategori_id) {
            $missing[] = 'Kategori';
        }

        if (! $this->barang_id) {
            $missing[] = 'Nama Barang';
        }

        if (! in_array($this->sifat_barang, ['D', 'S'], true)) {
            $missing[] = 'Sifat (D/S)';
        }

        if ($this->sifat_barang === 'D' && ! $this->penanggung_jawab_id) {
            $missing[] = 'PIC (NIA)';
        }

        if ($this->sifat_barang === 'S' && ! $this->lokasi_id) {
            $missing[] = 'Lokasi';
        }

        if (! $this->divisi_id) {
            $missing[] = 'Divisi';
        }

        if (! in_array($this->cara_perolehan, ['1', '2'], true)) {
            $missing[] = 'Cara Perolehan';
        }

        if (! in_array($this->status_barang, ['1', '2'], true)) {
            $missing[] = 'Status Barang';
        }

        if (! $this->tanggal_pembelian) {
            $missing[] = 'Tanggal Pembelian';
        }

        $this->missing_components = $missing;
        $this->is_ready = empty($missing);

        return $this->is_ready;
    }
}
