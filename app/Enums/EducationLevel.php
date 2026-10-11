<?php

namespace App\Enums;

enum EducationLevel: string
{
    case None = 'none';
    case Bac = 'bac';
    case Licence = 'licence';
    case Master = 'master';
    case Other = 'other';
}
