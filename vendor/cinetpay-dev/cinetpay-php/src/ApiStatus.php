<?php

declare(strict_types=1);

namespace CinetPay;

enum ApiStatus: string
{
    case Ok = 'OK';
    case Success = 'SUCCESS';
    case OperationError = 'OPERATION_ERROR';
    case NotFound = 'NOT_FOUND';
    case InvalidCredentials = 'INVALID_CREDENTIALS';
    case InvalidParams = 'INVALID_PARAMS';
    case ExpiredToken = 'EXPIRED_TOKEN';
    case InvalidToken = 'INVALID_TOKEN';
    case TransactionExist = 'TRANSACTION_EXIST';
    case Initiated = 'INITIATED';
    case Pending = 'PENDING';
    case Expired = 'EXPIRED';
    case OtpError = 'OTP_ERROR';
    case OtpExpired = 'OTP_EXPIRED';
    case InsufficientBalance = 'INSUFFICIENT_BALANCE';
    case UserNotFound = 'USER_NOT_FOUND';
    case UserIsBlocked = 'USER_IS_BLOCKED';
    case Failed = 'FAILED';
    case NotAllowed = 'NOT_ALLOWED';

    public function isSuccessful(): bool
    {
        return $this === self::Success;
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::Success,
            self::TransactionExist,
            self::InsufficientBalance,
            self::Failed,
        ], true);
    }
}
