<?php

namespace App\Helpers;

class DateHelper
{
    public static function arabicDate($date)
    {
        if (!$date) return null;

        return $date->format('d/m/Y ');
    }
}
