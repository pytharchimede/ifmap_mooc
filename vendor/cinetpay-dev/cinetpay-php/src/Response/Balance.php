<?php

declare(strict_types=1);

namespace CinetPay\Response;

use CinetPay\Support\Data;

final readonly class Balance
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public int $code,
        public string $status,
        public string $availableBalance,
        public string $currency,
        public array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            code: Data::integer($data, 'code'),
            status: Data::string($data, 'status'),
            availableBalance: Data::string($data, 'available_balance'),
            currency: Data::string($data, 'currency'),
            raw: $data,
        );
    }
}
