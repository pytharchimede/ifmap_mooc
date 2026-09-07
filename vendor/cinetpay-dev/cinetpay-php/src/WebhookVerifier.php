<?php

declare(strict_types=1);

namespace CinetPay;

use CinetPay\Exception\WebhookVerificationException;

final class WebhookVerifier
{
    public static function verifyNotifyToken(string $receivedToken, string $expectedToken): bool
    {
        if ($receivedToken === '' || $expectedToken === '') {
            return false;
        }

        return hash_equals($expectedToken, $receivedToken);
    }

    public static function assertNotifyToken(string $receivedToken, string $expectedToken): void
    {
        if (! self::verifyNotifyToken($receivedToken, $expectedToken)) {
            throw new WebhookVerificationException('The CinetPay webhook notify token is invalid.');
        }
    }
}
