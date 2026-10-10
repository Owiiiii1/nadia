<?php

namespace App\Enums;

enum LeadSource: string
{
    case Form = 'form';
    case Chat = 'chat';

    public function label(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return match ($this) {
            self::Form => match ($locale) {
                'ru' => 'Форма',
                'uk' => 'Форма',
                default => 'Form',
            },
            self::Chat => match ($locale) {
                'ru' => 'Чат',
                'uk' => 'Чат',
                default => 'Chat',
            },
        };
    }
}
