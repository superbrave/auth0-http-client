<?php

namespace Superbrave\AuthZeroHttpClient;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * Handles authentication with Auth0 before actual requests are made.
 *
 * @author Niels Nijens <nn@superbrave.nl>
 */
class AuthZeroAuthenticatingHttpClient implements HttpClientInterface
{
    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * @var AuthZeroConfiguration
     */
    private $authZeroConfiguration;

    /**
     * Constructs a new AuthZeroAuthenticatingHttpClient instance.
     *
     * @param HttpClientInterface   $client
     * @param AuthZeroConfiguration $authZeroConfiguration
     */
    public function __construct(HttpClientInterface $client, AuthZeroConfiguration $authZeroConfiguration)
    {
        $this->client = $client;
        $this->authZeroConfiguration = $authZeroConfiguration;
    }

    /**
     * Requests a JSON Web Token at Auth0 before executing the requested request.
     *
     * {@inheritdoc}
     */
    public function request(string $method, string $url, array $options = array()): ResponseInterface
    {
        $response = $this->client->request(
            'POST',
            $this->authZeroConfiguration->getTenantTokenUrl(),
            array(
                'json' => $this->authZeroConfiguration->getAuthenticationPayload(),
            )
        );

        $responseJson = null;
        if ($response->getStatusCode() === 200) {
            $responseJson = $response->toArray();
        }

        if (isset($responseJson['access_token'])) {
            $options['auth_bearer'] = $responseJson['access_token'];
        }

        return $this->client->request($method, $url, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }
}
