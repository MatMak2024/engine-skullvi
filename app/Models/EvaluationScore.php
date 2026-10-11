<?php

namespace App\Models;

use App\Enums\ScoringCriterion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['evaluation_id', 'criterion', 'points', 'max_points', 'explanation'])]
class EvaluationScore extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<Evaluation, $this>
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'criterion' => ScoringCriterion::class,
            'points' => 'decimal:2',
            'max_points' => 'decimal:2',
        ];
    }
}
