<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root me-redirect ke dashboard; tamu lalu diarahkan ke login.
     */
    public function test_root_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('dashboard'));
        $this->followingRedirects()->get('/')->assertOk(); // berakhir di halaman login
    }
}
