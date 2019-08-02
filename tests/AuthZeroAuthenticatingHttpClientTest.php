<?php

namespace Superbrave\AuthZeroHttpClient\Tests;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Superbrave\AuthZeroHttpClient\AuthZeroAuthenticatingHttpClient;
use Superbrave\AuthZeroHttpClient\AuthZeroConfiguration;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Tests the @see AuthZeroAuthenticatingHttpClient class.
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
     * @var MockResponse[]
     */
    private $mockResponses;

    /**
     * Creates a new AuthZeroAuthenticatingClient instance for testing.
     */
    protected function setUp(): void
    {
        $this->mockResponses = new ArrayIterator();

        $mockHttpClient = new MockHttpClient($this->mockResponses);
        $this->authZeroConfiguration = new AuthZeroConfiguration(
            'https://dev-1234.auth0.com',
            'clientId',
            'clientSecret',
            'https://www.superbrave.nl/api'
        );

        $this->httpClient = new AuthZeroAuthenticatingHttpClient($mockHttpClient, $this->authZeroConfiguration);
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
            array(
                'http_code' => 200,
            )
        );
        $this->mockResponses[] = new MockResponse('{"message": "Response from actual API."}');

        /** @var MockResponse $response */
        $response = $this->httpClient->request('GET', 'https://superbrave.nl/api');

        $this->assertSame(
            array(
                'authorization' => array(
                    sprintf('Bearer %s', $accessToken),
                ),
            ),
            $response->getRequestOptions()['headers']
        );
    }

    /**
     * Tests if AuthZeroAuthenticatingClient::request when unsuccessfully authenticating with Auth0
     * still continues the request.
     */
    public function testRequestWithAuthZeroAuthenticationUnsuccessful()
    {
        $this->mockResponses[] = new MockResponse(
            '{"error": "access_denied", "error_description": "Unauthorized"}',
            array(
                'http_code' => 401,
            )
        );
        $this->mockResponses[] = new MockResponse('{"message": "Response from actual API."}');

        /** @var MockResponse $response */
        $response = $this->httpClient->request('GET', 'https://superbrave.nl/api');

        $this->assertSame(
            array(),
            $response->getRequestOptions()['headers']
        );
    }

    /**
     * Tests if AuthZeroAuthenticatingHttpClient::stream only calls the stream method on
     * the underlying/decorated HTTP client.
     */
    public function testStream()
    {
        $mockResponse = new MockResponse('');
        $expectedResponseStream = new ResponseStream(MockResponse::stream(array($mockResponse), null));

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
