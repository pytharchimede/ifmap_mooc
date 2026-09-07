<?php

declare(strict_types=1);

namespace CinetPay\Auth;

use CinetPay\Response\AccessToken;

interface TokenStore
{
    public function get(string $accountKey): ?AccessToken;

    public function put(string $accountKey, AccessToken $token): void;

    public function forget(string $accountKey): void;
}
