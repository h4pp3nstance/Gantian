<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_public_welcome_page_presents_gantian(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Gantian')
            ->assertSee('Enterprise rental management')
            ->assertSee('Sistem peminjaman barang')
            ->assertSee('Customer')
            ->assertSee('Staff')
            ->assertSee('Admin/Owner')
            ->assertDontSee('Laravel has wonderful documentation');
    }
}
