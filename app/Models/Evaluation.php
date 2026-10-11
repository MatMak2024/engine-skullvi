<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'application_id',
    'rubric_version',
    'total_score',
    'completeness_rate',
    'suggested_status',
    'flags',
    'is_current',
    'computed_at',
])]
class Evaluation extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return HasMany<EvaluationScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suggested_status' => ApplicationStatus::class,
            'flags' => 'array',
            'is_current' => 'boolean',
            'computed_at' => 'datetime',
        ];
    }
}
