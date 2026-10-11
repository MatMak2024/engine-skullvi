<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'is_open'])]
class Skill extends Model
{
    use HasUuids;

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_skill');
    }

    /**
     * @return BelongsToMany<Program, $this>
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_skill')
            ->withPivot('is_required');
    }

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_skill')
            ->withPivot('is_required');
    }

    /**
     * @return BelongsToMany<Offer, $this>
     */
    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'offer_skill')
            ->withPivot('is_required');
    }

    /**
     * @return BelongsToMany<Application, $this>
     */
    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'application_skill')
            ->withPivot('level');
    }

    /**
     * @param  Builder<Skill>  $query
     * @param  list<string>  $includeIds
     */
    public function scopeSelectable(Builder $query, array $includeIds = []): void
    {
        $query->where(function (Builder $query) use ($includeIds): void {
            $query->where('is_open', true);

            if ($includeIds !== []) {
                $query->orWhereIn('id', $includeIds);
            }
        });
    }

    /**
     * @param  list<string>  $ids
     * @param  list<string>  $alreadyAttached
     */
    public static function ensureSelectable(array $ids, array $alreadyAttached = []): void
    {
        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return;
        }

        $allowed = static::query()->selectable($alreadyAttached)->whereIn('id', $ids)->count();

        if ($allowed !== count($ids)) {
            throw ValidationException::withMessages([
                'required_skills' => 'Une compétence suspendue ne peut pas être ajoutée.',
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
        ];
    }
}
