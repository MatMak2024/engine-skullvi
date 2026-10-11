<?php

namespace App\Http\Requests;

use App\Models\Offer;
use Illuminate\Validation\Rule;

class StoreOfferApplicationRequest extends StoreApplicationRequest
{
    public function authorize(): bool
    {
        $offer = $this->route('offer');

        return $this->user() !== null
            && $offer instanceof Offer
            && $this->user()->can('apply', $offer);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $hasProfileCv = (bool) $this->user()?->candidate?->hasCv();

        return [
            ...parent::rules(),
            'cv' => [
                Rule::requiredIf(fn () => ! $this->boolean('use_profile_cv') || ! $hasProfileCv),
                'nullable',
                'file',
                'mimes:pdf',
                'max:5120',
            ],
            'motivation_text' => ['required', 'string', 'min:80', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cv.required' => 'Le CV est obligatoire pour postuler à une offre.',
            'motivation_text.required' => 'La lettre de motivation est obligatoire.',
            'motivation_text.min' => 'La lettre de motivation doit contenir au moins 80 caractères.',
        ];
    }
}
