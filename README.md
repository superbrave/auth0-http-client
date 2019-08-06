# Auth0 HTTP client

[![Latest version on Packagist][ico-version]][link-version]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-build]][link-build]
[![Coverage Status][ico-coverage]][link-coverage]
[![Code Quality][ico-code-quality]][link-code-quality]

[Auth0][link-auth0] API client authentication for the Symfony HTTP client.

## Installation using Composer
Run the following command to add the package to the composer.json of your project:

``` bash
$ composer require superbrave/auth0-http-client symfony/http-client
```

The `symfony/http-client` can be replaced with any other HTTP client implementing the Symfony HTTP client contracts.

## Usage
The following example shows how to create the instances required to do an API call authenticated through Auth0:
```php
<?php

use Superbrave\AuthZeroHttpClient\AuthZeroAuthenticatingHttpClient;
use Superbrave\AuthZeroHttpClient\AuthZeroConfiguration;
use Symfony\Component\HttpClient\HttpClient;

$httpClient = HttpClient::create();
$authZeroConfiguration = new AuthZeroConfiguration(
    'https://dev-1234.eu.auth0.com', // Your Auth0 tenant URL
    'clientId',                      // Your application's Client ID
    'clientSecret',                  // Your application's Client Secret
    'https://www.superbrave.nl/api'  // The unique identifier of the target API you want to access
);

$authZeroHttpClient = new AuthZeroAuthenticatingHttpClient($httpClient, $authZeroConfiguration);

$response = $this->httpClient->request('GET', 'https://superbrave.nl/api');
```

Optionally, a custom cache instance can be injected into the `AuthZeroAuthenticatingHttpClient`. The cache will store
the access tokens (JWT) for the configured TTL returned by Auth0.

## License
The Auth0 HTTP client is licensed under the MIT License. Please see the [LICENSE file](LICENSE.md) for details.

[ico-version]: https://img.shields.io/packagist/v/superbrave/auth0-http-client.svg
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg
[ico-build]: https://scrutinizer-ci.com/g/superbrave/auth0-http-client/badges/build.png?b=master
[ico-coverage]: https://scrutinizer-ci.com/g/superbrave/auth0-http-client/badges/coverage.png?b=master
[ico-code-quality]: https://scrutinizer-ci.com/g/superbrave/auth0-http-client/badges/quality-score.png?b=master

[link-version]: https://packagist.org/packages/superbrave/auth0-http-client
[link-build]: https://scrutinizer-ci.com/g/superbrave/auth0-http-client/build-status/master
[link-coverage]: https://scrutinizer-ci.com/g/superbrave/auth0-http-client/build-status/master
[link-code-quality]: https://scrutinizer-ci.com/g/superbrave/auth0-http-client/build-status/master
[link-auth0]: https://auth0.com
