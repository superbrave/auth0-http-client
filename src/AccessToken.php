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
     *
     * @param string $token
     * @param int    $ttl
     */
    public function __construct(string $token, int $ttl)
    {
        $this->token = $token;
        $this->ttl = $ttl;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }
}
