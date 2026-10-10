<?php

namespace App\Support;

class IpHasher
{
    public static function hash(string $ip): string
    {
        $day = now('UTC')->format('Y-m-d');

        return hash('sha256', $ip.'|'.$day.'|'.(string) config('app.key'));
    }
}
