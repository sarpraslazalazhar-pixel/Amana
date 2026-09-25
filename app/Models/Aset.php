<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aset extends Model
{
    use HasFactory;

    protected $table = 'aset';

    protected $fillable = [
        'nama_aset', 'sifat_barang', 'kode_aset', 'kode_aset_lama', 'kode_sistem_lama', 'kategori_id', 'barang_id', 'merk_id', 'tipe_model',
        'produsen', 'no_seri', 'tahun_produksi', 'nomor_urut', 'lokasi_id', 'divisi_id', 'penanggung_jawab_id',
        'cara_perolehan', 'status_barang',
        'deskripsi', 'tanggal_pembelian', 'toko_distributor', 'no_invoice',
        'jumlah_unit', 'harga_satuan', 'harga_total', 'umur_ekonomis_tahun',
        'penyusutan_per_bulan', 'nilai_residu', 'foto_utama', 'keterangan_tambahan', 'status',
        'nonaktif_sebab', 'nonaktif_keterangan',
        'jenis', 'created_by',
    ];

    protected $casts = [
        'tanggal_pembelian' => 'date',
        'harga_satuan' => 'decimal:2',
        'harga_total' => 'decimal:2',
        'penyusutan_per_bulan' => 'decimal:2',
        'nilai_residu' => 'decimal:2',
        'nomor_urut' => 'integer',
    ];

    protected $appends = ['klasifikasi'];

    public function getKlasifikasiAttribute(): string
    {
        if (! empty($this->jenis)) {
            return $this->jenis === 'kelolaan' ? 'Aset dalam Kelolaan' : 'Aset Tetap';
        }

        if ($this->relationLoaded('divisi') && $this->divisi) {
            return in_array($this->divisi->kode_divisi, ['5', '6'], true) ? 'Aset dalam Kelolaan' : 'Aset Tetap';
        }

        if ($this->divisi_id) {
            $kode = $this->divisi?->kode_divisi;
            if ($kode) {
                return in_array($kode, ['5', '6'], true) ? 'Aset dalam Kelolaan' : 'Aset Tetap';
            }
        }

        return 'Aset Tetap';
    }

    public function scopeKlasifikasi($query, string $klasifikasi)
    {
        $klasifikasi = strtolower($klasifikasi);
        if ($klasifikasi === 'kelolaan' || $klasifikasi === 'aset dalam kelolaan') {
            return $query->where(function ($q) {
                $q->where('jenis', 'kelolaan')
                    ->orWhere(function ($sub) {
                        $sub->whereNull('jenis')
                            ->whereHas('divisi', function ($d) {
                                $d->whereIn('kode_divisi', ['5', '6']);
                            });
                    });
            });
        } elseif ($klasifikasi === 'tetap' || $klasifikasi === 'aset tetap') {
            return $query->where(function ($q) {
                $q->where('jenis', 'tetap')
                    ->orWhere(function ($sub) {
                        $sub->whereNull('jenis')
                            ->whereHas('divisi', function ($d) {
                                $d->whereNotIn('kode_divisi', ['5', '6']);
                            });
                    });
            });
        }

        return $query;
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    public function merk()
    {
        return $this->belongsTo(Merk::class, 'merk_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function penanggungJawab()
    {
        return $this->belongsTo(PenanggungJawab::class, 'penanggung_jawab_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function riwayat()
    {
        return $this->hasMany(RiwayatAset::class, 'aset_id')->latest();
    }

    public function agenda()
    {
        return $this->hasMany(AgendaAset::class, 'aset_id')->latest();
    }

    public function keuangan()
    {
        return $this->hasMany(KeuanganAset::class, 'aset_id')->latest('tanggal');
    }

    public function jurnal()
    {
        return $this->hasMany(JurnalAset::class, 'aset_id')->latest('tanggal');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'aset_id')->latest();
    }

    public function lampiran()
    {
        return $this->hasMany(LampiranAset::class, 'aset_id')->latest();
    }
}
