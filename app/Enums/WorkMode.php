<?php

namespace App\Enums;

enum WorkMode: string
{
    case Remote = 'remote';
    case Onsite = 'onsite';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::Remote => 'Télétravail',
            self::Onsite => 'Sur place',
            self::Hybrid => 'Hybride',
        };
    }
}
