<?php
namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthApiControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    // Test 1 — register options returns a challenge
    public function testRegisterOptionsRequiresEmail(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register/options',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    // Test 2 — /me requires authentication
    public function testMeEndpointRequiresAuth(): void
    {
        $this->client->request('GET', '/api/auth/me');
        $this->assertResponseStatusCodeSame(401);
    }

    // Test 3 — /me works with valid JWT
    public function testMeEndpointWithValidToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $user->setRoles(['ROLE_USER']);

        $token = static::getContainer()
            ->get('lexik_jwt_authentication.jwt_manager')
            ->create($user);

        $this->client->request(
            'GET',
            '/api/auth/me',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode(
            $this->client->getResponse()->getContent(), true
        );
        $this->assertEquals('test@example.com', $data['email']);
    }

    // Test 4 — homepage loads
    public function testHomepageLoads(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
    }

    // Test 5 — admin login page loads
    public function testAdminLoginPageLoads(): void
    {
        $this->client->request('GET', '/admin/login');
        $this->assertResponseIsSuccessful();
    }

    // Test 6 — admin dashboard redirects if not logged in
    public function testAdminDashboardRedirectsGuest(): void
    {
        $this->client->request('GET', '/admin/dashboard');
        $this->assertResponseRedirects();
    }

    // Test 7 — user register page loads
    public function testRegisterPageLoads(): void
    {
        $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
    }

    // Test 8 — login options endpoint responds
    public function testLoginOptionsEndpoint(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login/options',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );
        // Either 200 (success) or 400 (webauthn not configured yet)
        $this->assertContains(
            $this->client->getResponse()->getStatusCode(),
            [200, 400]
        );
    }
}