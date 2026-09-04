<?php

declare(strict_types=1);

namespace CinetPay;

use InvalidArgumentException;

enum Country: string
{
    case IvoryCoast = 'CI';
    case BurkinaFaso = 'BF';
    case Mali = 'ML';
    case Senegal = 'SN';
    case Togo = 'TG';
    case Guinea = 'GN';
    case Cameroon = 'CM';
    case Benin = 'BJ';
    case CongoKinshasa = 'CD';
    case Niger = 'NE';
    case Chad = 'TD';
    case CongoBrazzaville = 'CG';
    case CentralAfricanRepublic = 'CF';
    case Gabon = 'GA';
    case EquatorialGuinea = 'GQ';

    public function currency(): Currency
    {
        return match ($this) {
            self::IvoryCoast,
            self::BurkinaFaso,
            self::Mali,
            self::Senegal,
            self::Togo,
            self::Benin,
            self::Niger => Currency::XOF,
            self::Guinea => Currency::GNF,
            self::Cameroon,
            self::Chad,
            self::CongoBrazzaville,
            self::CentralAfricanRepublic,
            self::Gabon,
            self::EquatorialGuinea => Currency::XAF,
            self::CongoKinshasa => Currency::CDF,
        };
    }

    public function callingCode(): string
    {
        return match ($this) {
            self::IvoryCoast => '+225',
            self::BurkinaFaso => '+226',
            self::Mali => '+223',
            self::Senegal => '+221',
            self::Togo => '+228',
            self::Guinea => '+224',
            self::Cameroon => '+237',
            self::Benin => '+229',
            self::CongoKinshasa => '+243',
            self::Niger => '+227',
            self::Chad => '+235',
            self::CongoBrazzaville => '+242',
            self::CentralAfricanRepublic => '+236',
            self::Gabon => '+241',
            self::EquatorialGuinea => '+240',
        };
    }

    public function assertCurrency(Currency $currency): void
    {
        if ($currency !== $this->currency()) {
            throw new InvalidArgumentException(sprintf(
                'Currency "%s" does not match the CinetPay account country "%s"; expected "%s".',
                $currency->value,
                $this->value,
                $this->currency()->value,
            ));
        }
    }

    public function assertPaymentMethod(?string $paymentMethod): void
    {
        if ($paymentMethod === null) {
            return;
        }

        $expectedSuffix = '_'.$this->value;

        if (! str_ends_with($paymentMethod, $expectedSuffix)) {
            throw new InvalidArgumentException(sprintf(
                'Payment method "%s" does not match the CinetPay account country "%s"; expected suffix "%s".',
                $paymentMethod,
                $this->value,
                $expectedSuffix,
            ));
        }
    }

    public function assertPhoneNumber(?string $phoneNumber): void
    {
        if ($phoneNumber === null) {
            return;
        }

        if (preg_match('/^\+[1-9][0-9]{7,14}$/', $phoneNumber) !== 1) {
            throw new InvalidArgumentException('The phone number must use the E.164 format.');
        }

        if (! str_starts_with($phoneNumber, $this->callingCode())) {
            throw new InvalidArgumentException(sprintf(
                'Phone number "%s" does not match the CinetPay account country "%s"; expected calling code "%s".',
                $phoneNumber,
                $this->value,
                $this->callingCode(),
            ));
        }
    }
}
