<?php

namespace Superbrave\AuthZeroHttpClient;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;
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
     * @var AdapterInterface
     */
    private $accessTokenCache;

    /**
     * Constructs a new AuthZeroAuthenticatingHttpClient instance.
     *
     * @param HttpClientInterface   $client
     * @param AuthZeroConfiguration $authZeroConfiguration
     * @param AdapterInterface|null $accessTokenCache
     */
    public function __construct(
        HttpClientInterface $client,
        AuthZeroConfiguration $authZeroConfiguration,
        AdapterInterface $accessTokenCache = null
    ) {
        $this->client = $client;
        $this->authZeroConfiguration = $authZeroConfiguration;

        if ($accessTokenCache === null) {
            $accessTokenCache = new ArrayAdapter();
        }

        $this->accessTokenCache = $accessTokenCache;
    }

    /**
     * Requests a JSON Web Token at Auth0 before executing the requested request.
     *
     * {@inheritdoc}
     */
    public function request(string $method, string $url, array $options = array()): ResponseInterface
    {
        $this->appendAuthBearerToRequestOptions($options);

        return $this->client->request($method, $url, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    /**
     * Appends the 'auth_bearer' option with the retrieved access token from Auth0.
     *
     * @param array $options
     */
    private function appendAuthBearerToRequestOptions(array &$options): void
    {
        if (isset($options['auth_bearer'])) {
            return;
        }

        $accessToken = $this->getAccessTokenFromCache();
        if ($accessToken !== null) {
            $options['auth_bearer'] = $accessToken->getToken();
        }
    }

    /**
     * Returns an access token from the cache by the configured audience in the AuthZeroConfiguration.
     *
     * @return AccessToken|null
     */
    private function getAccessTokenFromCache(): ?AccessToken
    {
        // Replace invalid cache key characters with an underscore.
        $cacheKey = preg_replace('#[\{\}\(\)\/\\\@:]+#', '_', $this->authZeroConfiguration->getAudience());

        $accessToken = $this->accessTokenCache->getItem($cacheKey);
        if ($accessToken->isHit()) {
            return $accessToken->get();
        }

        $newAccessToken = $this->getNewAccessTokenForCache($accessToken);

        $this->accessTokenCache->save($accessToken);

        return $newAccessToken;
    }

    /**
     * Requests and returns a new AccessToken.
     * This method is called by the access token cache on a cache miss.
     *
     * @param CacheItem $item
     *
     * @return AccessToken|null
     */
    private function getNewAccessTokenForCache(CacheItem $item): ?AccessToken
    {
        $accessToken = $this->requestAccessToken();

        if ($accessToken !== null) {
            $item->set($accessToken);
            $item->expiresAfter($accessToken->getTtl());
        }

        return $accessToken;
    }

    /**
     * Requests an access token at Auth0.
     *
     * @return AccessToken|null
     */
    private function requestAccessToken(): ?AccessToken
    {
        $response = $this->client->request(
            'POST',
            $this->authZeroConfiguration->getTenantTokenUrl(),
            array(
                'json' => $this->authZeroConfiguration->getAuthenticationPayload(),
            )
        );

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $responseJson = $response->toArray();
        if (isset($responseJson['access_token'], $responseJson['expires_in']) === false) {
            return null;
        }

        return new AccessToken($responseJson['access_token'], $responseJson['expires_in']);
    }
}
