<?php

namespace App\Http\Requests\Admin;

use App\Models\Skill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class SaveSkillRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'category_id' => $this->filled('category_id') ? $this->input('category_id') : null,
            'category' => $this->filled('category') ? trim((string) $this->input('category')) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $skill = $this->route('skill');
        $skillId = $skill instanceof Skill ? $skill->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('skills', 'name')->ignore($skillId)],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'category' => ['required_without:category_id', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la compétence est obligatoire.',
            'name.unique' => 'Cette compétence existe déjà.',
            'category.required_without' => 'Choisissez une catégorie ou saisissez-en une nouvelle.',
            'category_id.exists' => 'La catégorie choisie n’existe pas.',
        ];
    }
}
