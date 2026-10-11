<?php

namespace App\Support;

class UserAgent
{
    /**
     * @return array{device: string, browser: string, os: string}
     */
    public static function parse(?string $userAgent): array
    {
        $ua = $userAgent ?? '';

        return [
            'device' => self::device($ua),
            'browser' => self::browser($ua),
            'os' => self::os($ua),
        ];
    }

    private static function device(string $ua): string
    {
        if (preg_match('/iPad|Tablet|Kindle|Silk/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/Android/i', $ua) && ! preg_match('/Mobile/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/Mobile|iPhone|iPod/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private static function browser(string $ua): string
    {
        if (preg_match('/Edg\/|Edge\//i', $ua)) {
            return 'Edge';
        }

        if (preg_match('/SamsungBrowser/i', $ua)) {
            return 'Samsung';
        }

        if (preg_match('/OPR\/|Opera/i', $ua)) {
            return 'Opera';
        }

        if (preg_match('/Chrome\/|CriOS/i', $ua)) {
            return 'Chrome';
        }

        if (preg_match('/Firefox\/|FxiOS/i', $ua)) {
            return 'Firefox';
        }

        if (preg_match('/Safari\//i', $ua)) {
            return 'Safari';
        }

        return 'Other';
    }

    private static function os(string $ua): string
    {
        if (preg_match('/iPad|iPhone|iPod/i', $ua)) {
            return 'iOS';
        }

        if (preg_match('/Android/i', $ua)) {
            return 'Android';
        }

        if (preg_match('/Windows/i', $ua)) {
            return 'Windows';
        }

        if (preg_match('/Mac OS X|Macintosh/i', $ua)) {
            return 'macOS';
        }

        if (preg_match('/Linux/i', $ua)) {
            return 'Linux';
        }

        return 'Other';
    }
}
