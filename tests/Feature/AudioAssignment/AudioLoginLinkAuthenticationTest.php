<?php

namespace Tests\Feature\AudioAssignment;

use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * **Feature: audio-assignment-system, Property 5: Login link authentication round-trip**
 * 
 * For any valid login token generated for an audio assignment, using that token should 
 * authenticate the user and provide access to the correct audio.
 * 
 * **Validates: Requirements 3.4**
 */
class AudioLoginLinkAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create();
    }

    public function test_valid_login_token_authenticates_user_and_redirects_to_audio(): void
    {
        // Property test: for any user and audio, generating a login link should authenticate and redirect correctly
        $testCases = [
            ['userCount' => 1, 'audioCount' => 1],
            ['userCount' => 3, 'audioCount' => 2],
            ['userCount' => 5, 'audioCount' => 3],
        ];
        
        foreach ($testCases as $case) {
            $users = User::factory()->count($case['userCount'])->create();
            $audios = Audio::factory()->count($case['audioCount'])->create([
                'created_by' => $this->admin->id,
                'is_active' => true,
            ]);
            
            foreach ($users as $user) {
                foreach ($audios as $audio) {
                    // Generate login link
                    $loginLink = $user->generateAudioLoginLink($audio->id);
                    
                    // Extract URL and query parameters
                    $parsedUrl = parse_url($loginLink);
                    parse_str($parsedUrl['query'] ?? '', $queryParams);
                    
                    // Verify token was stored
                    $user->refresh();
                    $this->assertNotNull($user->login_token);
                    $this->assertNotNull($user->login_token_expires_at);
                    
                    // Follow the login link
                    $response = $this->get($loginLink);
                    
                    // Should redirect to audio show page
                    $response->assertRedirect(route('audio.show', $audio->id));
                    
                    // User should be authenticated
                    $this->assertTrue(Auth::check());
                    $this->assertEquals($user->id, Auth::id());
                    
                    // Token should be cleared (single-use)
                    $user->refresh();
                    $this->assertNull($user->login_token);
                    $this->assertNull($user->login_token_expires_at);
                    
                    // Logout for next iteration
                    Auth::logout();
                }
            }
        }
    }

    public function test_expired_token_redirects_to_login_with_error(): void
    {
        $user = User::factory()->create();
        $audio = Audio::factory()->create([
            'created_by' => $this->admin->id,
            'is_active' => true,
        ]);
        
        // Generate login link
        $loginLink = $user->generateAudioLoginLink($audio->id);
        
        // Manually expire the token
        $user->update([
            'login_token_expires_at' => now()->subHour(),
        ]);
        
        // Attempt to use expired link
        $response = $this->get($loginLink);
        
        // Should redirect to login with error
        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
        
        // User should not be authenticated
        $this->assertFalse(Auth::check());
    }

    public function test_invalid_token_redirects_to_login_with_error(): void
    {
        $user = User::factory()->create();
        $audio = Audio::factory()->create([
            'created_by' => $this->admin->id,
            'is_active' => true,
        ]);
        
        // Generate login link
        $loginLink = $user->generateAudioLoginLink($audio->id);
        
        // Tamper with the stored token hash (not the URL)
        $user->update([
            'login_token' => hash('sha256', 'wrong_token'),
        ]);
        
        // Attempt to use link with mismatched token
        $response = $this->get($loginLink);
        
        // Should redirect to login with error
        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
        
        // User should not be authenticated
        $this->assertFalse(Auth::check());
    }

    public function test_token_is_single_use_and_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $audio = Audio::factory()->create([
            'created_by' => $this->admin->id,
            'is_active' => true,
        ]);
        
        // Generate login link
        $loginLink = $user->generateAudioLoginLink($audio->id);
        
        // Use the link once
        $response = $this->get($loginLink);
        $response->assertRedirect(route('audio.show', $audio->id));
        
        // Logout
        Auth::logout();
        
        // Attempt to use the same link again
        $response = $this->get($loginLink);
        
        // Should redirect to login with error (token was cleared)
        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
        
        // User should not be authenticated
        $this->assertFalse(Auth::check());
    }

    public function test_nonexistent_audio_redirects_to_dashboard_with_warning(): void
    {
        $user = User::factory()->create();
        $audio = Audio::factory()->create([
            'created_by' => $this->admin->id,
            'is_active' => true,
        ]);
        
        // Generate login link
        $loginLink = $user->generateAudioLoginLink($audio->id);
        
        // Delete the audio
        $audio->delete();
        
        // Use the link
        $response = $this->get($loginLink);
        
        // Should redirect to dashboard with warning
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('warning');
        
        // User should still be authenticated (token was valid)
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_login_link_contains_correct_audio_id(): void
    {
        $user = User::factory()->create();
        $audios = Audio::factory()->count(5)->create([
            'created_by' => $this->admin->id,
            'is_active' => true,
        ]);
        
        foreach ($audios as $audio) {
            // Generate login link
            $loginLink = $user->generateAudioLoginLink($audio->id);
            
            // Parse URL to extract audio ID
            $parsedUrl = parse_url($loginLink);
            $pathParts = explode('/', $parsedUrl['path']);
            
            // The audio ID should be in the URL path
            $this->assertContains((string) $audio->id, $pathParts);
            
            // Clear token for next iteration
            $user->update([
                'login_token' => null,
                'login_token_expires_at' => null,
            ]);
        }
    }

    public function test_token_hash_is_stored_not_plain_token(): void
    {
        $user = User::factory()->create();
        $audio = Audio::factory()->create([
            'created_by' => $this->admin->id,
            'is_active' => true,
        ]);
        
        // Generate login link
        $loginLink = $user->generateAudioLoginLink($audio->id);
        
        // Extract token from URL
        $parsedUrl = parse_url($loginLink);
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $plainToken = $queryParams['token'] ?? null;
        
        $this->assertNotNull($plainToken);
        
        // Verify stored token is hashed
        $user->refresh();
        $this->assertNotEquals($plainToken, $user->login_token);
        $this->assertEquals(hash('sha256', $plainToken), $user->login_token);
    }
}
