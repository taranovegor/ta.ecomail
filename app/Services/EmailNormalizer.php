<?php

namespace App\Services;

class EmailNormalizer
{
    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }
}
