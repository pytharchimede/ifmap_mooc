<?php

declare(strict_types=1);

namespace CinetPay\Http;

use CinetPay\Config;
use CinetPay\Exception\ApiException;
use CinetPay\Exception\AuthenticationException;
use CinetPay\Exception\TransportException;
use CinetPay\Exception\ValidationException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final readonly class HttpTransport
{
    public function __construct(
        private Config $config,
        private ClientInterface $client,
    ) {
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, ?array $payload = null, ?string $token = null): array
    {
        $options = [
            'connect_timeout' => $this->config->connectTimeout,
            'timeout' => $this->config->timeout,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'cinetpay-php-sdk/2.0',
            ],
        ];

        if ($token !== null) {
            $options['headers']['Authorization'] = 'Bearer '.$token;
        }

        if ($payload !== null) {
            $options['json'] = $payload;
        }

        try {
            $response = $this->client->request(
                strtoupper($method),
                $this->config->resolvedBaseUrl().'/'.ltrim($path, '/'),
                $options,
            );
        } catch (GuzzleException $exception) {
            throw new TransportException(
                'The request to CinetPay could not be completed: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        return $this->decode($response);
    }

    /** @return array<string, mixed> */
    private function decode(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = [];

        if ($body !== '') {
            try {
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new ApiException(
                    message: 'CinetPay returned an invalid JSON response.',
                    httpStatus: $response->getStatusCode(),
                    response: ['body' => $body],
                );
            }

            if (! is_array($decoded)) {
                throw new ApiException(
                    message: 'CinetPay returned an unexpected JSON response.',
                    httpStatus: $response->getStatusCode(),
                    response: ['body' => $body],
                );
            }

            foreach ($decoded as $key => $value) {
                if (! is_string($key)) {
                    throw new ApiException(
                        message: 'CinetPay returned an unexpected JSON response.',
                        httpStatus: $response->getStatusCode(),
                        response: ['body' => $body],
                    );
                }

                $data[$key] = $value;
            }
        }

        $httpStatus = $response->getStatusCode();
        $apiCode = is_numeric($data['code'] ?? null) ? (int) $data['code'] : null;
        $apiStatus = is_string($data['status'] ?? null) ? $data['status'] : null;
        $message = $this->errorMessage($data, $response->getReasonPhrase());

        if ($httpStatus === 401 || in_array($apiStatus, [
            'INVALID_CREDENTIALS',
            'INVALID_TOKEN',
            'EXPIRED_TOKEN',
        ], true)) {
            throw new AuthenticationException($message, $httpStatus, $apiCode, $apiStatus, $data);
        }

        if ($httpStatus === 422) {
            throw new ValidationException($message, $httpStatus, $apiCode, $apiStatus, $data);
        }

        if ($httpStatus < 200 || $httpStatus >= 300) {
            throw new ApiException($message, $httpStatus, $apiCode, $apiStatus, $data);
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function errorMessage(array $data, string $fallback): string
    {
        foreach (['description', 'message', 'status'] as $key) {
            if (is_scalar($data[$key] ?? null) && (string) $data[$key] !== '') {
                return (string) $data[$key];
            }
        }

        return $fallback !== '' ? $fallback : 'CinetPay API request failed.';
    }
}
