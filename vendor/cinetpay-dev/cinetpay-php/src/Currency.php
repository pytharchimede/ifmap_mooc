<?php

declare(strict_types=1);

namespace CinetPay;

enum Currency: string
{
    case XOF = 'XOF';
    case XAF = 'XAF';
    case GNF = 'GNF';
    case CDF = 'CDF';
}
