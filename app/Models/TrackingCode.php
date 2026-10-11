<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingCode extends Model
{
    protected $fillable = [
        'name',
        'placement',
        'code',
        'is_active',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }
}
