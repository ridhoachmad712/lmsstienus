<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** Root membuka pemilih sistem tanpa mewajibkan login. */
    public function test_root_redirects_guest_to_system_selector(): void
    {
        $this->get('/')->assertRedirect(route('portal.index'));
        $this->followingRedirects()->get('/')->assertOk()->assertSee('Pilih Sistem');
    }
}
