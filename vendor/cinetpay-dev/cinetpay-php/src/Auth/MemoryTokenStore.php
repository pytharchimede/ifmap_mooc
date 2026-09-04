<?php

declare(strict_types=1);

namespace CinetPay\Auth;

use CinetPay\Response\AccessToken;

final class MemoryTokenStore implements TokenStore
{
    /** @var array<string, AccessToken> */
    private array $tokens = [];

    public function get(string $accountKey): ?AccessToken
    {
        $token = $this->tokens[$accountKey] ?? null;

        if ($token?->isExpired()) {
            unset($this->tokens[$accountKey]);

            return null;
        }

        return $token;
    }

    public function put(string $accountKey, AccessToken $token): void
    {
        $this->tokens[$accountKey] = $token;
    }

    public function forget(string $accountKey): void
    {
        unset($this->tokens[$accountKey]);
    }
}
