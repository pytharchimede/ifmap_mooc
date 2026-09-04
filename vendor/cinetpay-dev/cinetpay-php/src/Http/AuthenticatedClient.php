<?php

declare(strict_types=1);

namespace CinetPay\Http;

use CinetPay\Auth\Authenticator;
use CinetPay\Exception\AuthenticationException;

final readonly class AuthenticatedClient
{
    public function __construct(
        private HttpTransport $transport,
        private Authenticator $authenticator,
    ) {
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, ?array $payload = null): array
    {
        $token = $this->authenticator->token();

        try {
            return $this->transport->request($method, $path, $payload, $token->token);
        } catch (AuthenticationException) {
            $refreshedToken = $this->authenticator->token(forceRefresh: true);

            return $this->transport->request($method, $path, $payload, $refreshedToken->token);
        }
    }
}
