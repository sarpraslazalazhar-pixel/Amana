<?php

namespace Tests\Feature;

use Tests\TestCase;

class Custom404PageTest extends TestCase
{
    public function test_custom_404_page_is_rendered_with_correct_assets_and_text(): void
    {
        $response = $this->get('/halaman-ini-tidak-akan-pernah-ada-' . uniqid());

        $response->assertStatus(404);
        $response->assertSee('Oops...', false);
        $response->assertSee('Terjadi Kesalahan', false);
        $response->assertSee('Halaman yang Anda cari tidak dapat ditemukan', false);
        $response->assertSee('image.png', false);
        $response->assertSee('logo-amana.png', false);
        $response->assertSee('catgip.webp', false);
        $response->assertSee('Hayo nyasar ya:v', false);
        $response->assertSee('https://instagram.com/gbrnmewing', false);
        $response->assertSee('IT dev', false);
        $response->assertSee('Kembali ke Beranda', false);
        $response->assertSee('Coba Lagi', false);
        $response->assertSee('Aset Tertata, Kinerja Meningkat', false);
    }
}
