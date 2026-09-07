<?php

declare(strict_types=1);

namespace CinetPay\Response;

use CinetPay\Support\Data;

final readonly class User
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phoneNumber,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Data::string($data, 'name'),
            email: Data::string($data, 'email'),
            phoneNumber: Data::string($data, 'phone_number'),
        );
    }
}
