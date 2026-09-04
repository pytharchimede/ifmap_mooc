<?php

declare(strict_types=1);

namespace CinetPay\Support;

use InvalidArgumentException;

final class Assert
{
    public static function notEmpty(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(sprintf('The "%s" field cannot be empty.', $field));
        }
    }

    public static function length(string $value, string $field, int $minimum, int $maximum): void
    {
        $length = mb_strlen($value);

        if ($length < $minimum || $length > $maximum) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" field must contain between %d and %d characters.',
                $field,
                $minimum,
                $maximum,
            ));
        }
    }

    public static function maximumLength(string $value, string $field, int $maximum): void
    {
        if (mb_strlen($value) > $maximum) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" field cannot exceed %d characters.',
                $field,
                $maximum,
            ));
        }
    }

    public static function positiveInteger(int $value, string $field): void
    {
        if ($value <= 0) {
            throw new InvalidArgumentException(sprintf('The "%s" field must be greater than zero.', $field));
        }
    }

    public static function email(string $value, string $field): void
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(sprintf('The "%s" field must be a valid email address.', $field));
        }
    }

    public static function url(string $value, string $field): void
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException(sprintf('The "%s" field must be a valid URL.', $field));
        }
    }
}
