<?php

namespace App\Models;

use App\Enums\WorkMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_open', 'work_mode', 'work_country', 'work_city', 'requested_documents'])]
class Program extends Model
{
    use HasUuids;

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'program_skill')
            ->withPivot('is_required');
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function requiredSkills(): BelongsToMany
    {
        return $this->skills()->wherePivot('is_required', true);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function bonusSkills(): BelongsToMany
    {
        return $this->skills()->wherePivot('is_required', false);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'work_mode' => WorkMode::class,
            'requested_documents' => 'array',
        ];
    }
}
