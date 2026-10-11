<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class SaveCourseRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_open' => $this->boolean('is_open'),
            'program_id' => $this->filled('program_id') ? $this->input('program_id') : null,
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
            'program_id' => ['nullable', 'uuid', 'exists:programs,id'],
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
            'name.required' => 'Le nom du cours est obligatoire.',
            'program_id.exists' => 'Le programme choisi n’existe pas.',
            'required_skills.*.exists' => 'Une compétence requise n’existe pas.',
            'bonus_skills.*.exists' => 'Une compétence bonus n’existe pas.',
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
        });
    }
}
