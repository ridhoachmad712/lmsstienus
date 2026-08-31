<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** Root adalah halaman depan publik untuk dua aplikasi mandiri. */
    public function test_root_is_public_application_directory(): void
    {
        $this->get('/')->assertOk()->assertSee('Layanan Akademik');
    }
}
