<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsetGrupTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_redirect_ke_grup_tetap(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/aset')
            ->assertRedirect(route('aset.tetap'));
    }

    public function test_halaman_aset_tetap_tampil(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/aset/tetap')
            ->assertOk()
            ->assertSeeText('Aset Tetap');
    }

    public function test_halaman_aset_kelolaan_tampil(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/aset/kelolaan')
            ->assertOk()
            ->assertSeeText('Aset Kelolaan');
    }

    public function test_halaman_aset_non_aktif_tampil_dengan_kolom_jenis(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/aset/non-aktif')
            ->assertOk()
            ->assertSeeText('Aset Non Aktif')
            ->assertSeeText('Jenis');
    }

    public function test_ekspor_excel_aset_tetap_mengembalikan_file_xlsx(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/aset/tetap/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_filter_dan_summary_bekerja_dengan_baik(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/aset/tetap?search=laptop')
            ->assertOk()
            ->assertSeeText('Total Aset Terfilter')
            ->assertSeeText('Total Unit Fisik')
            ->assertSeeText('Akumulasi Nilai');
    }

    public function test_per_page_custom_mengatur_jumlah_pagination(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get('/aset/tetap?per_page=25');

        $response->assertOk();
        $this->assertSame(25, $response->viewData('asetList')->perPage());
    }

    public function test_per_page_all_mengatur_pagination_menjadi_500(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get('/aset/tetap?per_page=all');

        $response->assertOk();
        $this->assertSame(500, $response->viewData('asetList')->perPage());
    }

    public function test_per_page_invalid_fallback_ke_default_15(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get('/aset/tetap?per_page=invalid');

        $response->assertOk();
        $this->assertSame(15, $response->viewData('asetList')->perPage());
    }
}
