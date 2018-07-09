<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

class NotificationRuleDirectionType extends AbstractEnumType
{
    public const CHEAP = 'cheap';
    public const EXPENSIVE = 'expensive';

    protected static $choices = [
        self::CHEAP => 'Cheap',
        self::EXPENSIVE => 'Expensive',
    ];
}