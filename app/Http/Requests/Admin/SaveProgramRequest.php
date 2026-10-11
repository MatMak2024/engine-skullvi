<?php

namespace App\Http\Requests\Admin;

use App\Enums\WorkMode;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveProgramRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_open' => $this->boolean('is_open'),
            'work_mode' => $this->input('work_mode', WorkMode::Remote->value),
            'work_country' => $this->filled('work_country') ? trim((string) $this->input('work_country')) : null,
            'work_city' => $this->filled('work_city') ? trim((string) $this->input('work_city')) : null,
            'required_skills' => $this->input('required_skills', []),
            'bonus_skills' => $this->input('bonus_skills', []),
            'course_ids' => $this->input('course_ids', []),
            'requested_documents' => $this->input('requested_documents'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_open' => ['required', 'boolean'],
            'work_mode' => ['required', Rule::enum(WorkMode::class)],
            'work_country' => ['nullable', 'string', 'max:255', 'required_if:work_mode,onsite'],
            'work_city' => ['nullable', 'string', 'max:255', 'required_if:work_mode,onsite'],
            'required_skills' => ['array'],
            'required_skills.*' => ['uuid', 'exists:skills,id'],
            'bonus_skills' => ['array'],
            'bonus_skills.*' => ['uuid', 'exists:skills,id'],
            'course_ids' => ['array'],
            'requested_documents' => ['nullable', 'string', 'max:1000'],
            'course_ids.*' => ['uuid', 'exists:courses,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du programme est obligatoire.',
            'work_mode.required' => 'Le mode de travail est obligatoire.',
            'work_mode.enum' => 'Le mode de travail doit être télétravail, sur place ou hybride.',
            'work_country.required_if' => 'Le pays du lieu est obligatoire pour une offre sur place.',
            'work_city.required_if' => 'La ville du lieu est obligatoire pour une offre sur place.',
            'required_skills.*.exists' => 'Une compétence requise n’existe pas.',
            'bonus_skills.*.exists' => 'Une compétence bonus n’existe pas.',
            'course_ids.*.exists' => 'Un cours choisi n’existe pas.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $overlap = array_intersect(
                $this->input('required_skills', []),
                $this->input('bonus_skills', []),
            );

            if ($overlap !== []) {
                $validator->errors()->add(
                    'bonus_skills',
                    'Une compétence ne peut pas être à la fois requise et bonus.',
                );
            }

            $program = $this->route('program');
            $programId = $program instanceof Program ? $program->id : null;
            $taken = Course::query()
                ->whereIn('id', $this->input('course_ids', []))
                ->whereNotNull('program_id')
                ->when(
                    $programId !== null,
                    fn ($query) => $query->where('program_id', '!=', $programId),
                )
                ->exists();

            if ($taken) {
                $validator->errors()->add(
                    'course_ids',
                    'Un cours choisi appartient déjà à un autre programme.',
                );
            }
        });
    }
}
