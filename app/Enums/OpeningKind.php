<?php

namespace App\Enums;

enum OpeningKind: string
{
    case Program = 'program';
    case Course = 'course';
    case Offer = 'offer';

    public function label(): string
    {
        return match ($this) {
            self::Program => 'Programme',
            self::Course => 'Cours',
            self::Offer => 'Offre',
        };
    }
}
