<?php

namespace App\Enums;

enum LeadGoal: string
{
    case Sell = 'sell';
    case Buy = 'buy';
    case Invest = 'invest';
    case Consult = 'consult';

    public function label(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return match ($this) {
            self::Sell => match ($locale) {
                'ru' => 'Продажа',
                'uk' => 'Продаж',
                default => 'Sell a property',
            },
            self::Buy => match ($locale) {
                'ru' => 'Покупка',
                'uk' => 'Купівля',
                default => 'Buy a home',
            },
            self::Invest => match ($locale) {
                'ru' => 'Инвестиции',
                'uk' => 'Інвестиції',
                default => 'Invest in Chicago',
            },
            self::Consult => match ($locale) {
                'ru' => 'Консультация',
                'uk' => 'Консультація',
                default => 'Talk through my options',
            },
        };
    }
}
