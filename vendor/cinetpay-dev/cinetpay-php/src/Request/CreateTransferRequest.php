<?php

declare(strict_types=1);

namespace CinetPay\Request;

use CinetPay\Currency;
use CinetPay\Support\Assert;
use InvalidArgumentException;

final readonly class CreateTransferRequest
{
    /**
     * @param array<string, scalar|null> $extra
     */
    public function __construct(
        public Currency $currency,
        public string $merchantTransactionId,
        public string $phoneNumber,
        public int $amount,
        public string $paymentMethod,
        public string $reason,
        public string $notifyUrl,
        public int|string|null $userId = null,
        public array $extra = [],
    ) {
        if ($this->currency === Currency::CDF) {
            throw new InvalidArgumentException('CDF is not supported by the transfer API.');
        }

        Assert::length($this->merchantTransactionId, 'merchant_transaction_id', 1, 30);
        Assert::notEmpty($this->phoneNumber, 'phone_number');
        Assert::positiveInteger($this->amount, 'amount');
        Assert::notEmpty($this->paymentMethod, 'payment_method');
        Assert::notEmpty($this->reason, 'reason');
        Assert::url($this->notifyUrl, 'notify_url');
        Assert::maximumLength($this->notifyUrl, 'notify_url', 120);
    }

    /** @return array<string, scalar|null> */
    public function toArray(): array
    {
        return array_filter(array_merge($this->extra, [
            'currency' => $this->currency->value,
            'merchant_transaction_id' => $this->merchantTransactionId,
            'phone_number' => $this->phoneNumber,
            'amount' => $this->amount,
            'payment_method' => $this->paymentMethod,
            'reason' => $this->reason,
            'notify_url' => $this->notifyUrl,
            'user_id' => $this->userId,
        ]), static fn (mixed $value): bool => $value !== null);
    }
}
