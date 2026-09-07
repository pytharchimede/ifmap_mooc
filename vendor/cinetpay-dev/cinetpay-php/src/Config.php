<?php

declare(strict_types=1);

namespace CinetPay;

use InvalidArgumentException;

final readonly class Config
{
    public function __construct(
        public string $apiKey,
        public string $apiPassword,
        public Country $country,
        public Environment $environment = Environment::Sandbox,
        public float $timeout = 15.0,
        public float $connectTimeout = 5.0,
        public ?string $baseUrl = null,
    ) {
        if (trim($this->apiKey) === '') {
            throw new InvalidArgumentException('The CinetPay API key cannot be empty.');
        }

        if (trim($this->apiPassword) === '') {
            throw new InvalidArgumentException('The CinetPay API password cannot be empty.');
        }

        if ($this->timeout <= 0 || $this->connectTimeout <= 0) {
            throw new InvalidArgumentException('HTTP timeouts must be greater than zero.');
        }

        if ($this->baseUrl !== null && filter_var($this->baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('The custom CinetPay base URL is invalid.');
        }
    }

    public function resolvedBaseUrl(): string
    {
        return rtrim($this->baseUrl ?? $this->environment->baseUrl(), '/');
    }

    public function tokenCacheKey(): string
    {
        return 'cinetpay:'.hash('sha256', implode("\0", [
            $this->apiKey,
            $this->country->value,
            $this->environment->value,
            $this->resolvedBaseUrl(),
        ]));
    }
}
