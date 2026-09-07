<?php

declare(strict_types=1);

namespace CinetPay\Webhook;

use CinetPay\Exception\InvalidWebhookException;
use CinetPay\Response\User;
use CinetPay\Support\Data;
use JsonException;

final readonly class WebhookNotification
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public string $notifyToken,
        public string $merchantTransactionId,
        public string $transactionId,
        public ?User $reportedUser,
        public array $raw,
    ) {
        if ($this->notifyToken === '') {
            throw new InvalidWebhookException('The CinetPay webhook is missing "notify_token".');
        }

        if ($this->merchantTransactionId === '') {
            throw new InvalidWebhookException('The CinetPay webhook is missing "merchant_transaction_id".');
        }

        if ($this->transactionId === '') {
            throw new InvalidWebhookException('The CinetPay webhook is missing "transaction_id".');
        }
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $user = Data::associativeArray($payload['user'] ?? null);

        return new self(
            notifyToken: Data::string($payload, 'notify_token'),
            merchantTransactionId: Data::string($payload, 'merchant_transaction_id'),
            transactionId: Data::string($payload, 'transaction_id'),
            reportedUser: $user === [] ? null : User::fromArray($user),
            raw: $payload,
        );
    }

    public static function fromJson(string $payload): self
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidWebhookException('The CinetPay webhook body is not valid JSON.', previous: $exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new InvalidWebhookException('The CinetPay webhook body must be a JSON object.');
        }

        $payload = [];

        foreach ($decoded as $key => $value) {
            if (! is_string($key)) {
                throw new InvalidWebhookException('The CinetPay webhook body must use string field names.');
            }

            $payload[$key] = $value;
        }

        return self::fromArray($payload);
    }

    public function deduplicationKey(): string
    {
        return $this->transactionId;
    }
}
