<?php

namespace Superbrave\AuthZeroHttpClient\Tests;

use PHPUnit\Framework\TestCase;
use Superbrave\AuthZeroHttpClient\AuthZeroConfiguration;

/**
 * Tests the @see AuthZeroConfiguration class.
 *
 * @author Niels Nijens <nn@superbrave.nl>
 */
class AuthZeroConfigurationTest extends TestCase
{
    /**
     * @var AuthZeroConfiguration
     */
    private $configuration;

    /**
     * Creates a new AuthZeroConfiguration for testing.
     */
    protected function setUp(): void
    {
        $this->configuration = new AuthZeroConfiguration(
            'https://dev-1234.auth0.com',
            'clientId',
            'clientSecret',
            'https://www.superbrave.nl/api'
        );
    }

    /**
     * Tests if AuthZeroConfiguration::getTenantTokenUrl returns the expected URL to request an access token.
     */
    public function testGetTenantTokenUrl()
    {
        $this->assertSame(
            'https://dev-1234.auth0.com/oauth/token',
            $this->configuration->getTenantTokenUrl()
        );
    }

    /**
     * Tests if AuthZeroConfiguration::getAuthenticationPayload returns the expected payload to request an access token.
     */
    public function testGetAuthenticationPayload()
    {
        $this->assertSame(
            [
                'client_id' => 'clientId',
                'client_secret' => 'clientSecret',
                'audience' => 'https://www.superbrave.nl/api',
                'grant_type' => 'client_credentials',
            ],
            $this->configuration->getAuthenticationPayload()
        );
    }
}
