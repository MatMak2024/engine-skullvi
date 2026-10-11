<?php

namespace App\Http\Requests;

use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Enums\SkillLevel;
use App\Http\Requests\Concerns\UsesFrenchValidation;
use App\Models\Program;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    use UsesFrenchValidation;

    public function authorize(): bool
    {
        $program = $this->route('program');

        return $this->user() !== null
            && $program instanceof Program
            && $this->user()->can('apply', $program);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'education_level' => ['required', Rule::enum(EducationLevel::class)],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'years_experience' => ['required', 'integer', 'min:0', 'max:80'],
            'projects_count' => ['required', 'integer', 'min:0', 'max:100'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'availability' => ['required', Rule::enum(Availability::class)],
            'motivation_text' => ['required', 'string', 'max:5000'],
            'use_profile_cv' => ['sometimes', 'boolean'],
            'cv' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'mimes:pdf', 'max:5120'],
            'skills' => ['nullable', 'array'],
            'skills.*.id' => ['required', 'uuid', 'exists:skills,id'],
            'skills.*.level' => ['required', Rule::enum(SkillLevel::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => 'téléphone',
            'country' => 'pays',
            'city' => 'ville',
            'education_level' => 'niveau d\'études',
            'field_of_study' => 'domaine d\'études',
            'years_experience' => 'années d\'expérience',
            'projects_count' => 'nombre de projets',
            'github_url' => 'lien GitHub',
            'portfolio_url' => 'lien portfolio',
            'linkedin_url' => 'lien LinkedIn',
            'availability' => 'disponibilité',
            'motivation_text' => 'motivation',
            'cv' => 'CV',
            'skills' => 'compétences',
        ];
    }
}
