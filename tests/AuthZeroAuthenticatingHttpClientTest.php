<?php

namespace Superbrave\AuthZeroHttpClient\Tests;

use PHPUnit\Framework\TestCase;
use Superbrave\AuthZeroHttpClient\AuthZeroAuthenticatingHttpClient;
use Superbrave\AuthZeroHttpClient\AuthZeroConfiguration;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Tests the @see AuthZeroAuthenticatingHttpClient class.
 *
 * @author Niels Nijens <nn@superbrave.nl>
 */
class AuthZeroAuthenticatingHttpClientTest extends TestCase
{
    /**
     * @var AuthZeroAuthenticatingHttpClient
     */
    private $httpClient;

    /**
     * @var AuthZeroConfiguration
     */
    private $authZeroConfiguration;

    /**
     * @var MockHttpClient
     */
    private $mockHttpClient;

    /**
     * @var MockResponse[]
     */
    private $mockResponses;

    /**
     * Creates a new AuthZeroAuthenticatingClient instance for testing.
     */
    protected function setUp(): void
    {
        $this->mockResponses = new \ArrayIterator();

        $this->mockHttpClient = new MockHttpClient($this->mockResponses);
        $this->authZeroConfiguration = new AuthZeroConfiguration(
            'https://dev-1234.auth0.com',
            'clientId',
            'clientSecret',
            'https://www.superbrave.nl/api'
        );

        $this->httpClient = new AuthZeroAuthenticatingHttpClient($this->mockHttpClient, $this->authZeroConfiguration);
    }

    /**
     * Tests if AuthZeroAuthenticatingClient::request when successfully authenticating with Auth0
     * adds the provided access token as authorization bearer to the request.
     */
    public function testRequestWithAuthZeroAuthenticationSuccessful()
    {
        $accessToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6IlFrVXhORVpDT';

        $this->mockResponses[] = new MockResponse(
            sprintf('{"access_token": "%s", "expires_in": 86400, "token_type": "Bearer"}', $accessToken),
            [
                'http_code' => 200,
            ]
        );
        $this->mockResponses[] = new MockResponse('{"message": "Response from actual API."}');

        /** @var MockResponse $response */
        $response = $this->httpClient->request('GET', 'https://superbrave.nl/api');

        $this->assertSame(
            [
                'accept' => ['Accept: */*'],
                'authorization' => [
                    sprintf('Authorization: Bearer %s', $accessToken),
                ],
            ],
            $response->getRequestOptions()['normalized_headers']
        );
    }

    /**
     * Tests if the access token is read from the cache on the second request.
     */
    public function testRequestReadsAccessTokenFromCache()
    {
        $accessToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6IlFrVXhORVpDT';

        $this->mockResponses[] = new MockResponse(
            sprintf('{"access_token": "%s", "expires_in": 86400, "token_type": "Bearer"}', $accessToken),
            [
                'http_code' => 200,
            ]
        );
        $this->mockResponses[] = new MockResponse('{"message": "Response from actual API."}');
        $this->mockResponses[] = new MockResponse('{"message": "Another response from actual API."}');

        /** @var MockResponse $response */
        $response = $this->httpClient->request('GET', 'https://superbrave.nl/api');

        $this->assertSame(
            [
                'accept' => ['Accept: */*'],
                'authorization' => [
                    sprintf('Authorization: Bearer %s', $accessToken),
                ],
            ],
            $response->getRequestOptions()['normalized_headers']
        );
        $this->assertSame('{"message": "Response from actual API."}', $response->getContent());

        $response = $this->httpClient->request('GET', 'https://superbrave.nl/api');

        $this->assertSame(
            [
                'accept' => ['Accept: */*'],
                'authorization' => [
                    sprintf('Authorization: Bearer %s', $accessToken),
                ],
            ],
            $response->getRequestOptions()['normalized_headers']
        );
        $this->assertSame('{"message": "Another response from actual API."}', $response->getContent());
    }

    /**
     * Tests if AuthZeroAuthenticatingClient::request does not replace an existing 'auth_bearer' option and thus
     * the cache is not called.
     */
    public function testRequestDoesNotReplaceExistingAuthBearer()
    {
        $cacheMock = $this->getMockBuilder(CacheInterface::class)
            ->getMock();
        $cacheMock->expects($this->never())
            ->method('get');

        $this->mockResponses[] = new MockResponse('{"message": "Response from actual API."}');

        $this->httpClient = new AuthZeroAuthenticatingHttpClient(
            $this->mockHttpClient,
            $this->authZeroConfiguration,
            $cacheMock
        );

        $this->httpClient->request('GET', 'https://superbrave.nl/api', ['auth_bearer' => 'token']);
    }

    /**
     * Tests if AuthZeroAuthenticatingClient::request when unsuccessfully authenticating with Auth0
     * still continues the request.
     */
    public function testRequestWithAuthZeroAuthenticationUnsuccessful()
    {
        $this->mockResponses[] = new MockResponse(
            '{"error": "access_denied", "error_description": "Unauthorized"}',
            [
                'http_code' => 401,
            ]
        );
        $this->mockResponses[] = new MockResponse('{"message": "Response from actual API."}');

        /** @var MockResponse $response */
        $response = $this->httpClient->request('GET', 'https://superbrave.nl/api');

        $this->assertSame(
            [
                'accept' => ['Accept: */*'],
            ],
            $response->getRequestOptions()['normalized_headers']
        );
    }

    /**
     * Tests if AuthZeroAuthenticatingClient::request still continues the request when Auth0 returns an
     * unexpected response without access token.
     */
    public function testRequestWithAuthZeroAuthenticationUnexpectedResponse()
    {
        $this->mockResponses[] = new MockResponse(
            '{}',
            [
                'http_code' => 200,
            ]
        );
        $this->mockResponses[] = new MockResponse('{"message": "Response from actual API."}');

        /** @var MockResponse $response */
        $response = $this->httpClient->request('GET', 'https://superbrave.nl/api');

        $this->assertSame(
            [
                'accept' => ['Accept: */*'],
            ],
            $response->getRequestOptions()['normalized_headers']
        );
    }

    /**
     * Tests if AuthZeroAuthenticatingHttpClient::stream only calls the stream method on
     * the underlying/decorated HTTP client.
     */
    public function testStream()
    {
        $mockResponse = new MockResponse('');
        $expectedResponseStream = new ResponseStream(MockResponse::stream([$mockResponse], null));

        $httpClientMock = $this->getMockBuilder(HttpClientInterface::class)
            ->getMock();
        $httpClientMock->expects($this->once())
            ->method('stream')
            ->with($mockResponse, null)
            ->willReturn($expectedResponseStream);

        $httpClient = new AuthZeroAuthenticatingHttpClient($httpClientMock, $this->authZeroConfiguration);

        $responseStream = $httpClient->stream($mockResponse);

        $this->assertSame($expectedResponseStream, $responseStream);
    }
}
