<?php

namespace Superbrave\AuthZeroHttpClient;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Handles authentication with Auth0 before actual requests are made.
 *
 * @author Niels Nijens <nn@superbrave.nl>
 */
class AuthZeroAuthenticatingHttpClient implements HttpClientInterface
{
    use DecoratorTrait;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly AuthZeroConfiguration $authZeroConfiguration,
        private ArrayAdapter|CacheInterface|null $accessTokenCache = null,
    ) {
        if ($this->accessTokenCache === null) {
            $this->accessTokenCache = new ArrayAdapter();
        }
    }

    /**
     * Requests a JSON Web Token at Auth0 before executing the requested request.
     *
     * {@inheritdoc}
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $this->appendAuthBearerToRequestOptions($options);

        return $this->client->request($method, $url, $options);
    }

    /**
     * Appends the 'auth_bearer' option with the retrieved access token from Auth0.
     */
    private function appendAuthBearerToRequestOptions(array &$options): void
    {
        if (isset($options['auth_bearer'])) {
            return;
        }

        $accessToken = $this->getAccessTokenFromCache();
        if ($accessToken instanceof AccessToken) {
            $options['auth_bearer'] = $accessToken->getToken();
        }
    }

    /**
     * Returns an access token from the cache by the configured audience in the AuthZeroConfiguration.
     */
    private function getAccessTokenFromCache(): ?AccessToken
    {
        // Replace invalid cache key characters with an underscore.
        $cacheKey = preg_replace('#[\{\}\(\)\/\\\@:]+#', '_', $this->authZeroConfiguration->getAudience());

        if (!$this->accessTokenCache instanceof CacheInterface) {
            return null;
        }

        return $this->accessTokenCache->get(
            $cacheKey,
            function (ItemInterface $item) {
                return $this->getNewAccessTokenForCache($item);
            }
        );
    }

    /**
     * Requests and returns a new AccessToken.
     *
     * This method is called by the access token cache on a cache miss.
     */
    private function getNewAccessTokenForCache(ItemInterface $item): ?AccessToken
    {
        $accessToken = $this->requestAccessToken();

        if ($accessToken !== null) {
            $item->expiresAfter($accessToken->getTtl());
        }

        return $accessToken;
    }

    /**
     * Requests an access token at Auth0.
     */
    private function requestAccessToken(): ?AccessToken
    {
        $response = $this->client->request(
            'POST',
            $this->authZeroConfiguration->getTenantTokenUrl(),
            [
                'json' => $this->authZeroConfiguration->getAuthenticationPayload(),
            ]
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
