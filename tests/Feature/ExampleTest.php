<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_redirects_to_dashboard_login(): void
    {
        $this->get('/')->assertRedirect(route('webhooks.dashboard.login'));
    }
}
