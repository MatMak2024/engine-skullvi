<?php

namespace App\Models;

use App\Enums\Availability;
use App\Enums\EducationLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'phone',
    'country',
    'city',
    'cv_path',
    'education_level',
    'field_of_study',
    'years_experience',
    'projects_count',
    'github_url',
    'portfolio_url',
    'linkedin_url',
    'availability',
    'interests',
    'other_skills',
])]
class Candidate extends Model
{
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'candidate_id', 'user_id');
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'candidate_skill', 'candidate_id', 'skill_id')
            ->withPivot('level');
    }

    /**
     * @return HasMany<CandidateDiploma, $this>
     */
    public function diplomas(): HasMany
    {
        return $this->hasMany(CandidateDiploma::class, 'candidate_id', 'user_id');
    }

    /**
     * @return HasMany<CandidateTraining, $this>
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(CandidateTraining::class, 'candidate_id', 'user_id');
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function hasCv(): bool
    {
        $path = $this->cv_path;

        return is_string($path)
            && str_starts_with($path, 'cvs/')
            && ! str_contains($path, '..')
            && Storage::disk('local')->exists($path);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
            'education_level' => EducationLevel::class,
            'availability' => Availability::class,
            'interests' => 'array',
            'other_skills' => 'array',
            'years_experience' => 'integer',
            'projects_count' => 'integer',
        ];
    }
}
