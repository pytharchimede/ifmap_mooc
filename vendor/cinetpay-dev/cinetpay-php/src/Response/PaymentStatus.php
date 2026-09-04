<?php

declare(strict_types=1);

namespace CinetPay\Response;

use CinetPay\ApiStatus;
use CinetPay\Support\Data;

final readonly class PaymentStatus
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public int $code,
        public string $status,
        public string $merchantTransactionId,
        public string $transactionId,
        public ?User $user,
        public array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $user = Data::associativeArray($data['user'] ?? null);

        return new self(
            code: Data::integer($data, 'code'),
            status: Data::string($data, 'status'),
            merchantTransactionId: Data::string($data, 'merchant_transaction_id'),
            transactionId: Data::string($data, 'transaction_id'),
            user: $user === [] ? null : User::fromArray($user),
            raw: $data,
        );
    }

    public function knownStatus(): ?ApiStatus
    {
        return ApiStatus::tryFrom($this->status);
    }

    public function isSuccessful(): bool
    {
        return $this->knownStatus()?->isSuccessful() ?? false;
    }

    public function isFinal(): bool
    {
        return $this->knownStatus()?->isFinal() ?? false;
    }
}
