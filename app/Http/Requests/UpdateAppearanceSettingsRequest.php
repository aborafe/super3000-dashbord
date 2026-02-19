<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppearanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('settings.manage');
    }

    public function rules(): array
    {
        return [
            'theme' => ['required', 'in:light,dark'],
            'rtl' => ['required', 'boolean'],
        ];
    }
}
