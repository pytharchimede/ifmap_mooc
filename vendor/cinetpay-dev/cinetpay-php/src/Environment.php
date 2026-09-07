<?php

declare(strict_types=1);

namespace CinetPay;

enum Environment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';

    public function baseUrl(): string
    {
        return match ($this) {
            self::Sandbox => 'https://api.cinetpay.net',
            self::Production => 'https://api.cinetpay.co',
        };
    }
}
