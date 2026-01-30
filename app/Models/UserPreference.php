<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'canvas_url',
        'horizon',
        'soft_cap',
        'hard_cap',
        'skip_weekends',
        'busy_weight',
    ];

    protected function casts(): array
    {
        return [
            'horizon' => 'integer',
            'soft_cap' => 'integer',
            'hard_cap' => 'integer',
            'skip_weekends' => 'boolean',
            'busy_weight' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
