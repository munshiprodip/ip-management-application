<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Security\Enums\UserRole;
use App\Models\ApiToken;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\Vrf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiV1AuthenticationAndScopesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $plainToken;
    private ApiToken $token;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::create(['name' => 'KYAMCH', 'slug' => 'kyamch-api', 'code' => 'KYAMCH-API']);
        $site = Site::create(['name' => 'Main Hospital', 'organization_id' => $org->id, 'code' => 'API-HQ']);
        Vrf::create(['name' => 'Default VRF', 'code' => 'VRF-GLOBAL', 'rd' => '65000:1', 'is_default' => true]);

        $this->user = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $created = ApiToken::createToken(
            name: 'Test Automation Token',
            scopes: ['read:vrfs', 'read:subnets'],
            user: $this->user,
            expiresInDays: 30,
            rateLimit: 5
        );

        $this->plainToken = $created['plain_token'];
        $this->token = $created['token'];
    }

    public function test_unauthenticated_request_is_rejected_with_401(): void
    {
        $response = $this->getJson('/api/v1/vrfs');

        $response->assertStatus(401);
        $response->assertJson([
            'error' => [
                'code' => 'UNAUTHENTICATED',
            ],
        ]);
    }

    public function test_invalid_bearer_token_is_rejected_with_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token_12345')
            ->getJson('/api/v1/vrfs');

        $response->assertStatus(401);
        $response->assertJson([
            'error' => [
                'code' => 'UNAUTHORIZED',
            ],
        ]);
    }

    public function test_revoked_token_is_rejected_with_401(): void
    {
        $this->token->revoke();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->getJson('/api/v1/vrfs');

        $response->assertStatus(401);
        $response->assertJson([
            'error' => [
                'code' => 'UNAUTHORIZED',
            ],
        ]);
    }

    public function test_expired_token_is_rejected_with_401(): void
    {
        $this->token->update(['expires_at' => now()->subDay()]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->getJson('/api/v1/vrfs');

        $response->assertStatus(401);
        $response->assertJson([
            'error' => [
                'code' => 'UNAUTHORIZED',
            ],
        ]);
    }

    public function test_token_lacking_required_scope_is_rejected_with_403(): void
    {
        // Token has 'read:vrfs' and 'read:subnets', but lacks 'write:allocations'
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson('/api/v1/ip-allocations', [
                'subnet_id' => 1,
            ]);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => [
                'code' => 'FORBIDDEN_SCOPE',
                'required_scope' => 'write:allocations',
            ],
        ]);
    }

    public function test_valid_token_with_scope_succeeds_and_records_usage(): void
    {
        $this->assertNull($this->token->last_used_at);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->getJson('/api/v1/vrfs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'code', 'rd', 'is_default'],
            ],
            'meta' => ['count', 'version'],
        ]);

        $this->token->refresh();
        $this->assertNotNull($this->token->last_used_at);
        $this->assertEquals('127.0.0.1', $this->token->last_used_ip);
    }

    public function test_rate_limiting_enforces_limit_and_returns_429(): void
    {
        RateLimiter::clear('api_token:' . $this->token->id);

        // Rate limit is configured to 5 req/min
        for ($i = 0; $i < 5; $i++) {
            $resp = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
                ->getJson('/api/v1/vrfs');
            $resp->assertStatus(200);
        }

        // 6th request triggers 429 Too Many Requests
        $exceeded = $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->getJson('/api/v1/vrfs');

        $exceeded->assertStatus(429);
        $exceeded->assertJson([
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
            ],
        ]);
        $this->assertTrue($exceeded->headers->has('Retry-After'));
    }
}
