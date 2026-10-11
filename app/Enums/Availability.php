<?php

namespace App\Enums;

enum Availability: string
{
    case Immediate = 'immediate';
    case OneMonth = 'one_month';
    case Later = 'later';
}
