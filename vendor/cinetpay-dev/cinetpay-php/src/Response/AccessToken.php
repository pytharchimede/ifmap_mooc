<?php

declare(strict_types=1);

namespace CinetPay\Response;

use CinetPay\Support\Data;
use UnexpectedValueException;

final readonly class AccessToken
{
    public function __construct(
        public string $token,
        public string $type,
        public int $expiresIn,
        public int $expiresAt,
    ) {
        if ($this->token === '') {
            throw new UnexpectedValueException('The authentication response does not contain an access token.');
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, int $issuedAt, int $expirationLeeway = 30): self
    {
        $nestedData = Data::associativeArray($data['data'] ?? null);
        $token = Data::string($data, 'access_token', Data::string($nestedData, 'token'));
        $expiresIn = max(0, Data::integer($data, 'expires_in', Data::integer($nestedData, 'expires_in', 300)));

        return new self(
            token: $token,
            type: Data::string($data, 'token_type', Data::string($nestedData, 'token_type', 'bearer')),
            expiresIn: $expiresIn,
            expiresAt: $issuedAt + max(0, $expiresIn - $expirationLeeway),
        );
    }

    public function isExpired(?int $at = null): bool
    {
        return ($at ?? time()) >= $this->expiresAt;
    }
}
