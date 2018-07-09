<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

class NotificationRuleUnitType extends AbstractEnumType
{
    public const PERCENT = 'percent';
    public const AMOUNT = 'amount';

    protected static $choices = [
        self::PERCENT => 'Percent',
        self::AMOUNT => 'Amount',
    ];
}