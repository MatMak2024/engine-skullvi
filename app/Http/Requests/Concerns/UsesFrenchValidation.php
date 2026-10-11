<?php

namespace App\Http\Requests\Concerns;

trait UsesFrenchValidation
{
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'string' => 'Le champ :attribute doit être une chaîne de caractères.',
            'integer' => 'Le champ :attribute doit être un nombre entier.',
            'url' => 'Le champ :attribute doit être une URL valide.',
            'uuid' => 'Le champ :attribute doit être un identifiant valide.',
            'exists' => 'La valeur du champ :attribute est invalide.',
            'enum' => 'La valeur du champ :attribute est invalide.',
            'array' => 'Le champ :attribute est invalide.',
            'file' => 'Le champ :attribute doit être un fichier.',
            'mimes' => 'Le champ :attribute doit être un fichier de type :values.',
            'max.string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'max.numeric' => 'Le champ :attribute ne doit pas dépasser :max.',
            'max.file' => 'Le champ :attribute ne doit pas dépasser :max kilo-octets.',
            'min.numeric' => 'Le champ :attribute doit être au moins :min.',
            'min.string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        ];
    }
}
