<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Spam = 'spam';

    public function label(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return match ($this) {
            self::New => match ($locale) {
                'ru' => 'Новая',
                'uk' => 'Нова',
                default => 'New',
            },
            self::InProgress => match ($locale) {
                'ru' => 'В работе',
                'uk' => 'В роботі',
                default => 'In progress',
            },
            self::Done => match ($locale) {
                'ru' => 'Готово',
                'uk' => 'Готово',
                default => 'Done',
            },
            self::Spam => match ($locale) {
                'ru' => 'Спам',
                'uk' => 'Спам',
                default => 'Spam',
            },
        };
    }
}
