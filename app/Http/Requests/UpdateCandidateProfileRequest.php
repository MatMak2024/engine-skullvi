<?php

namespace App\Http\Requests;

use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Enums\SkillLevel;
use App\Http\Requests\Concerns\UsesFrenchValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidateProfileRequest extends FormRequest
{
    use UsesFrenchValidation;

    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || $user->isPermanentlyRemovedAsCandidate()) {
            return false;
        }

        if ($user->candidate?->isSuspended()) {
            return false;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'education_level' => $this->filled('education_level') ? $this->input('education_level') : null,
            'availability' => $this->filled('availability') ? $this->input('availability') : null,
            'interests' => array_values(array_filter(
                (array) $this->input('interests', []),
                fn ($item) => filled($item),
            )),
        ]);
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
            'education_level' => ['nullable', Rule::enum(EducationLevel::class)],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'projects_count' => ['nullable', 'integer', 'min:0', 'max:100'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'availability' => ['nullable', Rule::enum(Availability::class)],
            'cv' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'interests' => ['nullable', 'array', 'max:20'],
            'interests.*' => ['required', 'string', 'max:100'],
            'skills' => ['nullable', 'array'],
            'skills.*.id' => ['required', 'uuid', 'exists:skills,id'],
            'skills.*.level' => ['required', Rule::enum(SkillLevel::class)],
            'other_skills' => ['nullable', 'array', 'max:20'],
            'other_skills.*.name' => ['required', 'string', 'max:100'],
            'other_skills.*.level' => ['required', Rule::enum(SkillLevel::class)],
            'diplomas' => ['nullable', 'array', 'max:20'],
            'diplomas.*.title' => ['required', 'string', 'max:255'],
            'diplomas.*.institution' => ['nullable', 'string', 'max:255'],
            'diplomas.*.year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'diplomas.*.description' => ['nullable', 'string', 'max:1000'],
            'trainings' => ['nullable', 'array', 'max:20'],
            'trainings.*.title' => ['required', 'string', 'max:255'],
            'trainings.*.organization' => ['nullable', 'string', 'max:255'],
            'trainings.*.year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'trainings.*.description' => ['nullable', 'string', 'max:1000'],
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
            'github_url' => 'GitHub',
            'portfolio_url' => 'portfolio',
            'linkedin_url' => 'LinkedIn',
            'availability' => 'disponibilité',
            'cv' => 'CV',
            'interests' => 'centres d\'intérêt',
            'skills' => 'compétences',
            'other_skills' => 'autres compétences',
            'diplomas' => 'diplômes',
            'trainings' => 'formations',
        ];
    }
}
