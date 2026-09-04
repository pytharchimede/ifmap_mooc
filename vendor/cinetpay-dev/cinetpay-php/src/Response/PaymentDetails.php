<?php

declare(strict_types=1);

namespace CinetPay\Response;

use CinetPay\ApiStatus;
use CinetPay\Support\Data;

final readonly class PaymentDetails
{
    public function __construct(
        public int $code,
        public string $status,
        public string $message,
        public bool $mustBeRedirected,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            code: Data::integer($data, 'code'),
            status: Data::string($data, 'status'),
            message: Data::string($data, 'message'),
            mustBeRedirected: filter_var($data['must_be_redirected'] ?? false, FILTER_VALIDATE_BOOL),
        );
    }

    public function knownStatus(): ?ApiStatus
    {
        return ApiStatus::tryFrom($this->status);
    }
}
