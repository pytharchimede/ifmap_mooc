<?php

declare(strict_types=1);

namespace CinetPay\Auth;

use CinetPay\Config;
use CinetPay\Exception\AuthenticationException;
use CinetPay\Exception\ApiException;
use CinetPay\Http\HttpTransport;
use CinetPay\Response\AccessToken;
use UnexpectedValueException;

final readonly class Authenticator
{
    public function __construct(
        private Config $config,
        private HttpTransport $transport,
        private TokenStore $tokens,
    ) {
    }

    public function token(bool $forceRefresh = false): AccessToken
    {
        $accountKey = $this->config->tokenCacheKey();

        if ($forceRefresh) {
            $this->tokens->forget($accountKey);
        }

        $cached = $this->tokens->get($accountKey);

        if ($cached !== null && ! $cached->isExpired()) {
            return $cached;
        }

        if ($cached !== null) {
            $this->tokens->forget($accountKey);
        }

        try {
            $data = $this->transport->request('POST', '/v1/oauth/login', [
                'api_key' => $this->config->apiKey,
                'api_password' => $this->config->apiPassword,
            ]);
            $token = AccessToken::fromArray($data, time());
        } catch (AuthenticationException $exception) {
            throw $exception;
        } catch (UnexpectedValueException $exception) {
            throw new AuthenticationException(
                message: $exception->getMessage(),
                httpStatus: 200,
                response: [],
            );
        } catch (ApiException $exception) {
            throw new AuthenticationException(
                message: $exception->getMessage(),
                httpStatus: $exception->httpStatus,
                apiCode: $exception->apiCode,
                apiStatus: $exception->apiStatus,
                response: $exception->response,
            );
        }

        $this->tokens->put($accountKey, $token);

        return $token;
    }

    public function forget(): void
    {
        $this->tokens->forget($this->config->tokenCacheKey());
    }
}
