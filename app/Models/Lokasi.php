<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    use HasFactory;

    protected $table = 'lokasi';

    protected $fillable = [
        'nama_lokasi',
        'gedung',
        'alamat_lengkap',
        'latitude',
        'longitude',
        'kode_lokasi',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected $appends = [
        'wilayah_nama',
        'wilayah_range',
    ];

    public static function getWilayahOptions(): array
    {
        return [
            '100-199' => [
                'prefix' => '1',
                'min' => 100,
                'max' => 199,
                'nama' => 'Kantor Cirendeu',
                'label' => '100 - 199 : Kantor Cirendeu',
            ],
            '200-299' => [
                'prefix' => '2',
                'min' => 200,
                'max' => 299,
                'nama' => 'KLB Pusat',
                'label' => '200 - 299 : KLB Pusat',
            ],
            '300-399' => [
                'prefix' => '3',
                'min' => 300,
                'max' => 399,
                'nama' => 'RGI Sawangan',
                'label' => '300 - 399 : RGI Sawangan',
            ],
            '400-499' => [
                'prefix' => '4',
                'min' => 400,
                'max' => 499,
                'nama' => 'RGI Sentra',
                'label' => '400 - 499 : RGI Sentra',
            ],
            '500-599' => [
                'prefix' => '5',
                'min' => 500,
                'max' => 599,
                'nama' => 'Kantor Layanan yang lain',
                'label' => '500 - 599 : Kantor Layanan yang lain',
            ],
            '600-699' => [
                'prefix' => '6',
                'min' => 600,
                'max' => 699,
                'nama' => 'Kantor Perwakilan',
                'label' => '600 - 699 : Kantor Perwakilan',
            ],
        ];
    }

    public static function parseWilayahRange(?string $input): ?array
    {
        if (empty($input)) {
            return null;
        }

        $input = trim($input);
        $options = self::getWilayahOptions();

        if (isset($options[$input])) {
            return $options[$input];
        }

        foreach ($options as $key => $opt) {
            if (
                $input === (string) $opt['min']
                || $input === $opt['prefix']
                || strcasecmp($input, $opt['nama']) === 0
                || strcasecmp($input, $opt['label']) === 0
            ) {
                return $opt;
            }
        }

        if (preg_match('/^([1-9]\d{0,2})\s*[-–]\s*([1-9]\d{0,2})$/', $input, $m)) {
            $min = (int) $m[1];
            $max = (int) $m[2];
            $prefix = substr((string) $min, 0, 1);

            return [
                'prefix' => $prefix,
                'min' => $min,
                'max' => $max,
                'nama' => "Rentang {$min}-{$max}",
                'label' => "Rentang {$min} - {$max}",
            ];
        }

        return null;
    }

    public function getWilayahNamaAttribute(): string
    {
        $code = (string) $this->kode_lokasi;
        $prefix = substr($code, 0, 1);

        return match ($prefix) {
            '1' => 'Kantor Cirendeu',
            '2' => 'KLB Pusat',
            '3' => 'RGI Sawangan',
            '4' => 'RGI Sentra',
            '5' => 'Kantor Layanan yang lain',
            '6' => 'Kantor Perwakilan',
            default => $this->gedung ?: 'Lainnya',
        };
    }

    public function getWilayahRangeAttribute(): string
    {
        $code = (string) $this->kode_lokasi;
        $prefix = substr($code, 0, 1);

        return match ($prefix) {
            '1' => '100-199',
            '2' => '200-299',
            '3' => '300-399',
            '4' => '400-499',
            '5' => '500-599',
            '6' => '600-699',
            default => '-',
        };
    }

    public function scopeInWilayahRange($query, string $rangeInput)
    {
        $range = self::parseWilayahRange($rangeInput);
        if (! $range) {
            return $query;
        }

        $min = (string) $range['min'];
        $max = (string) $range['max'];
        $prefix = (string) $range['prefix'];

        return $query->where(function ($q) use ($min, $max, $prefix) {
            $q->whereBetween('kode_lokasi', [$min, $max])
                ->orWhere('kode_lokasi', 'LIKE', "{$prefix}%");
        });
    }

    public function aset()
    {
        return $this->hasMany(Aset::class, 'lokasi_id');
    }
}
