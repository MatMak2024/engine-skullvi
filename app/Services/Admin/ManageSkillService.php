<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Models\Skill;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageSkillService
{
    /**
     * @param  array{name: string, category?: ?string, category_id?: ?string}  $data
     */
    public function save(array $data, ?Skill $skill = null): Skill
    {
        $skill ??= new Skill;
        $skill->fill(['name' => $data['name']]);
        $skill->save();

        $categoryId = $data['category_id'] ?? null;

        $skill->categories()->sync([
            $categoryId ?: $this->category((string) $data['category'])->id,
        ]);

        return $skill;
    }

    public function toggleSuspension(Skill $skill): bool
    {
        $skill->forceFill([
            'is_open' => ! $skill->is_open,
        ])->save();

        return ! $skill->is_open;
    }

    public function delete(Skill $skill): void
    {
        if ($skill->programs()->exists() || $skill->courses()->exists() || $skill->offers()->exists() || $skill->applications()->exists()) {
            throw ValidationException::withMessages([
                'skill' => 'Cette compétence est utilisée par un cours, une offre, un programme ou une candidature.',
            ]);
        }

        $skill->delete();
    }

    private function category(string $name): Category
    {
        $existing = Category::query()->where('name', $name)->first();

        if ($existing instanceof Category) {
            return $existing;
        }

        return Category::query()->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'categorie';
        $slug = $base;
        $suffix = 2;

        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
