<?php

declare(strict_types=1);

namespace CinetPay\Webhook;

use CinetPay\Response\PaymentStatus;

final readonly class ConfirmedPaymentNotification
{
    public function __construct(
        public WebhookNotification $notification,
        public PaymentStatus $payment,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->payment->isSuccessful();
    }

    public function isFinal(): bool
    {
        return $this->payment->isFinal();
    }
}
