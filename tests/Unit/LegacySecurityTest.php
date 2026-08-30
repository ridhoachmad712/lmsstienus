<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LegacySecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once dirname(__DIR__, 2).'/siakad-legacy/config/security.php';
        $_SESSION = ['_siakad_csrf' => str_repeat('a', 64)];
    }

    public function test_password_lama_tetap_bisa_diverifikasi_saat_migrasi(): void
    {
        $this->assertTrue(siakad_verify_password('rahasia', 'rahasia'));
        $this->assertTrue(siakad_verify_password('rahasia', md5('rahasia')));
        $this->assertTrue(siakad_verify_password('rahasia', password_hash('rahasia', PASSWORD_DEFAULT)));
        $this->assertFalse(siakad_verify_password('salah', md5('rahasia')));
    }

    public function test_form_dan_tautan_mutasi_lama_mendapat_token_csrf(): void
    {
        $html = '<form method="POST" action="x"><button>Simpan</button></form>'
            .'<a href="data?aksi=hapus&id=1">Hapus</a>';
        $secured = siakad_secure_output($html);

        $this->assertStringContainsString('name="_siakad_csrf"', $secured);
        $this->assertStringContainsString('_siakad_csrf='.str_repeat('a', 64), $secured);
    }

    public function test_halaman_tidak_dikenal_ditolak_secara_default(): void
    {
        $_SERVER['SCRIPT_FILENAME'] = '/app/pages/fitur_baru.php';
        $this->assertSame([], siakad_allowed_roles_for_script('fitur_baru'));
    }
}
