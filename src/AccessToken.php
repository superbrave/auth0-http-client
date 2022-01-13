<?php

namespace Superbrave\AuthZeroHttpClient;

/**
 * Contains the access token and expiry time returned by Auth0.
 *
 * @author Niels Nijens <nn@superbrave.nl>
 */
class AccessToken
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var int
     */
    private $ttl;

    /**
     * Constructs a new AccessToken instance.
     */
    public function __construct(string $token, int $ttl)
    {
        $this->token = $token;
        $this->ttl = $ttl;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }
}
