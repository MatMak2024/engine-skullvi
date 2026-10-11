<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\UsesFrenchValidation;
use App\Models\Application;
use App\Models\ReviewNote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewNoteRequest extends FormRequest
{
    use UsesFrenchValidation;

    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && ($this->user()?->can('create', [ReviewNote::class, $application]) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'note' => 'note',
        ];
    }
}
