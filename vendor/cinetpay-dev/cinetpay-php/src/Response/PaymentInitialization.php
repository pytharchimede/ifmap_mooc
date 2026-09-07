<?php

declare(strict_types=1);

namespace CinetPay\Response;

use CinetPay\ApiStatus;
use CinetPay\Support\Data;

final readonly class PaymentInitialization
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public int $code,
        public string $status,
        public string $merchantTransactionId,
        public string $transactionId,
        public ?string $notifyToken,
        public ?string $paymentToken,
        public ?string $paymentUrl,
        public ?PaymentDetails $details,
        public array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $details = Data::associativeArray($data['details'] ?? null);

        return new self(
            code: Data::integer($data, 'code'),
            status: Data::string($data, 'status'),
            merchantTransactionId: Data::string($data, 'merchant_transaction_id'),
            transactionId: Data::string($data, 'transaction_id'),
            notifyToken: Data::nullableString($data, 'notify_token'),
            paymentToken: Data::nullableString($data, 'payment_token'),
            paymentUrl: Data::nullableString($data, 'payment_url'),
            details: $details === [] ? null : PaymentDetails::fromArray($details),
            raw: $data,
        );
    }

    public function knownStatus(): ?ApiStatus
    {
        return ApiStatus::tryFrom($this->status);
    }
}
