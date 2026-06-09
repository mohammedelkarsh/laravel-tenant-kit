<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class OAuthLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_redirect_returns_404_when_provider_not_configured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get('http://'.config('app.central_domain').'/auth/google/redirect')
            ->assertNotFound();
    }

    public function test_oauth_redirect_returns_404_for_unknown_provider(): void
    {
        config([
            'services.google.client_id' => 'test-id',
            'services.google.client_secret' => 'test-secret',
        ]);

        $this->get('http://'.config('app.central_domain').'/auth/twitter/redirect')
            ->assertNotFound();
    }

    public function test_oauth_redirect_sends_user_to_provider_when_configured(): void
    {
        config([
            'services.github.client_id' => 'github-id',
            'services.github.client_secret' => 'github-secret',
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://github.com/login/oauth/authorize'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('github')
            ->andReturn($provider);

        $response = $this->get('http://'.config('app.central_domain').'/auth/github/redirect');

        $response->assertRedirect('https://github.com/login/oauth/authorize');
    }

    public function test_oauth_callback_creates_user_and_logs_in(): void
    {
        config([
            'services.google.client_id' => 'google-id',
            'services.google.client_secret' => 'google-secret',
        ]);

        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn('google-123');
        $socialUser->shouldReceive('getEmail')->andReturn('oauth@example.test');
        $socialUser->shouldReceive('getName')->andReturn('OAuth User');
        $socialUser->shouldReceive('getNickname')->andReturn(null);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get('http://'.config('app.central_domain').'/auth/google/callback');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'oauth@example.test',
            'oauth_provider' => 'google',
            'oauth_provider_id' => 'google-123',
        ]);
    }

    public function test_oauth_callback_links_existing_user_by_email(): void
    {
        config([
            'services.github.client_id' => 'github-id',
            'services.github.client_secret' => 'github-secret',
        ]);

        $existing = User::factory()->create([
            'email' => 'existing@example.test',
            'oauth_provider' => null,
            'oauth_provider_id' => null,
        ]);

        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn('gh-999');
        $socialUser->shouldReceive('getEmail')->andReturn('existing@example.test');
        $socialUser->shouldReceive('getName')->andReturn('Existing User');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('github')
            ->andReturn($provider);

        $this->get('http://'.config('app.central_domain').'/auth/github/callback')
            ->assertRedirect(route('dashboard', absolute: false));

        $existing->refresh();

        $this->assertSame('github', $existing->oauth_provider);
        $this->assertSame('gh-999', $existing->oauth_provider_id);
    }

    public function test_oauth_callback_rejects_missing_email(): void
    {
        config([
            'services.google.client_id' => 'google-id',
            'services.google.client_secret' => 'google-secret',
        ]);

        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn('google-456');
        $socialUser->shouldReceive('getEmail')->andReturn(null);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get('http://'.config('app.central_domain').'/auth/google/callback')
            ->assertStatus(422);
    }

    public function test_login_page_hides_oauth_buttons_when_not_configured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.github.client_id' => null,
            'services.github.client_secret' => null,
        ]);

        $this->get('http://'.config('app.central_domain').'/login')
            ->assertOk()
            ->assertDontSee(__('app.oauth.or_continue_with'), false);
    }

    public function test_login_page_shows_oauth_buttons_when_configured(): void
    {
        config([
            'services.google.client_id' => 'google-id',
            'services.google.client_secret' => 'google-secret',
            'services.github.client_id' => null,
            'services.github.client_secret' => null,
        ]);

        $this->get('http://'.config('app.central_domain').'/login')
            ->assertOk()
            ->assertSee(__('app.oauth.google'), false);
    }
}
