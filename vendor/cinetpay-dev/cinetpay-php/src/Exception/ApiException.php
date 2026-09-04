<?php

declare(strict_types=1);

namespace CinetPay\Exception;

class ApiException extends CinetPayException
{
    /**
     * @param array<string, mixed> $response
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus,
        public readonly ?int $apiCode = null,
        public readonly ?string $apiStatus = null,
        public readonly array $response = [],
    ) {
        parent::__construct($message, $apiCode ?? $httpStatus);
    }
}
