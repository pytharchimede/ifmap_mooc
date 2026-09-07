<?php

declare(strict_types=1);

namespace CinetPay\Request;

use CinetPay\Currency;
use CinetPay\Language;
use CinetPay\Support\Assert;
use InvalidArgumentException;

final readonly class CreatePaymentRequest
{
    /**
     * @param array<string, scalar|null> $extra
     */
    public function __construct(
        public Currency $currency,
        public string $merchantTransactionId,
        public int $amount,
        public string $successUrl,
        public string $failedUrl,
        public string $notifyUrl,
        public Language $language,
        public string $designation,
        public string $clientFirstName,
        public string $clientLastName,
        public string $clientEmail,
        public ?string $paymentMethod = null,
        public ?string $clientPhoneNumber = null,
        public bool $directPay = false,
        public ?string $otpCode = null,
        public array $extra = [],
    ) {
        Assert::length($this->merchantTransactionId, 'merchant_transaction_id', 1, 30);
        Assert::positiveInteger($this->amount, 'amount');
        Assert::url($this->successUrl, 'success_url');
        Assert::url($this->failedUrl, 'failed_url');
        Assert::url($this->notifyUrl, 'notify_url');
        Assert::maximumLength($this->successUrl, 'success_url', 120);
        Assert::maximumLength($this->failedUrl, 'failed_url', 120);
        Assert::maximumLength($this->notifyUrl, 'notify_url', 120);
        Assert::notEmpty($this->designation, 'designation');
        Assert::length($this->clientFirstName, 'client_first_name', 2, 255);
        Assert::length($this->clientLastName, 'client_last_name', 2, 255);
        Assert::email($this->clientEmail, 'client_email');

        if ($this->paymentMethod !== null) {
            Assert::notEmpty($this->paymentMethod, 'payment_method');
        }

        if ($this->clientPhoneNumber !== null) {
            Assert::notEmpty($this->clientPhoneNumber, 'client_phone_number');
        }

        if ($this->otpCode !== null) {
            Assert::length($this->otpCode, 'otp_code', 4, 6);
        }

        if ($this->directPay && $this->clientPhoneNumber === null) {
            throw new InvalidArgumentException('The "client_phone_number" field is required for direct payments.');
        }
    }

    /** @return array<string, scalar|null> */
    public function toArray(): array
    {
        return array_filter(array_merge($this->extra, [
            'currency' => $this->currency->value,
            'payment_method' => $this->paymentMethod,
            'merchant_transaction_id' => $this->merchantTransactionId,
            'otp_code' => $this->otpCode,
            'amount' => $this->amount,
            'success_url' => $this->successUrl,
            'failed_url' => $this->failedUrl,
            'notify_url' => $this->notifyUrl,
            'lang' => $this->language->value,
            'designation' => $this->designation,
            'client_first_name' => $this->clientFirstName,
            'client_last_name' => $this->clientLastName,
            'client_phone_number' => $this->clientPhoneNumber,
            'client_email' => $this->clientEmail,
            'direct_pay' => $this->directPay,
        ]), static fn (mixed $value): bool => $value !== null);
    }
}
