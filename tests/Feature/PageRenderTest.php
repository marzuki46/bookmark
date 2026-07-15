<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_authenticated_user_can_visit_all_pages(): void
    {
        $user = User::factory()->create();

        $pages = [
            'dashboard',
            'bookmarks',
            'notes',
            'collections',
            'tags',
            'prompts',
            'snippets',
            'files',
            'secrets',
            'ai',
            'search',
            'activity',
            'backup',
            'extension',
            'settings',
        ];

        foreach ($pages as $page) {
            $this->actingAs($user)->get(route($page))->assertOk();
        }
    }
}
