<?php

declare(strict_types=1);

namespace CinetPay\Webhook;

use CinetPay\Response\Transfer;

final readonly class ConfirmedTransferNotification
{
    public function __construct(
        public WebhookNotification $notification,
        public Transfer $transfer,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->transfer->isSuccessful();
    }

    public function isFinal(): bool
    {
        return $this->transfer->isFinal();
    }
}
