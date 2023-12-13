<?php

namespace Superbrave\AuthZeroHttpClient;

/**
 * Contains the access token and expiry time returned by Auth0.
 *
 * @author Niels Nijens <nn@superbrave.nl>
 */
readonly class AccessToken
{
    public function __construct(private string $token, private int $ttl)
    {
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
