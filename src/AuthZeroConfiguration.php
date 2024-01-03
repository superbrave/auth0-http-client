<?php

namespace Superbrave\AuthZeroHttpClient;

/**
 * The object containing the configuration values required for successful Machine-to-Machine
 * authentication with Auth0 using the Oauth2 Client Credentials Flow.
 *
 * @author Niels Nijens <nn@superbrave.nl>
 */
readonly class AuthZeroConfiguration
{
    /**
     * Constructs a new AuthZeroConfiguration instance.
     *
     * @param string $tenantUri    Your Auth0 tenant URL
     * @param string $clientId     Your application's Client ID
     * @param string $clientSecret Your application's Client Secret
     * @param string $audience     The unique identifier of the target API you want to access
     */
    public function __construct(
        private string $tenantUri,
        private string $clientId,
        private string $clientSecret,
        private string $audience
    ) {
    }

    public function getAudience(): string
    {
        return $this->audience;
    }

    /**
     * Returns the URL to request an Auth0 access token.
     */
    public function getTenantTokenUrl(): string
    {
        return sprintf('%s/oauth/token', $this->tenantUri);
    }

    /**
     * Returns the payload required to request an Auth0 access token.
     */
    public function getAuthenticationPayload(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'audience' => $this->audience,
            'grant_type' => 'client_credentials',
        ];
    }
}
