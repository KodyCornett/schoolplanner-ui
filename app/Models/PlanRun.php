<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanRun extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'paths',
        'settings',
        'preview_state',
    ];

    protected function casts(): array
    {
        return [
            'paths' => 'array',
            'settings' => 'array',
            'preview_state' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDateRangeAttribute(): ?array
    {
        $workBlocks = $this->preview_state['work_blocks'] ?? [];

        if (empty($workBlocks)) {
            return null;
        }

        $dates = array_column($workBlocks, 'date');
        sort($dates);

        return [
            'start' => $dates[0],
            'end' => $dates[count($dates) - 1],
        ];
    }
}
