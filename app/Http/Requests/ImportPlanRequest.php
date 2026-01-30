<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Canvas input: require either file or URL
            'canvas_ics' => ['nullable', 'file', 'mimes:ics,txt', 'max:5120', 'required_without:canvas_url'],
            'canvas_url' => ['nullable', 'url', 'required_without:canvas_ics'],

            // Busy optional (upload for MVP)
            'busy_ics' => ['nullable', 'file', 'mimes:ics,txt', 'max:5120'],

            // Settings (safe defaults later)
            'horizon' => ['nullable', 'integer', 'min:1', 'max:365'],
            'soft_cap' => ['nullable', 'integer', 'min:1', 'max:24'],
            'hard_cap' => ['nullable', 'integer', 'min:1', 'max:24'],
            'skip_weekends' => ['nullable', 'boolean'],
            'busy_weight' => ['nullable', 'numeric', 'min:0', 'max:10'],
        ];
    }
}
