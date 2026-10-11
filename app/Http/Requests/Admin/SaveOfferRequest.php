<?php

namespace App\Http\Requests\Admin;

use App\Enums\WorkMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveOfferRequest extends AdminRequest
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
            'requested_documents' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de l’offre est obligatoire.',
            'work_country.required_if' => 'Le pays du lieu est obligatoire pour une offre sur place.',
            'work_city.required_if' => 'La ville du lieu est obligatoire pour une offre sur place.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
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
        });
    }
}
