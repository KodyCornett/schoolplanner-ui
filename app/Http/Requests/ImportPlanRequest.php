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
        $maxHorizon = $this->user()->maxHorizon();

        return [
            // Canvas input: require either file or URL
            'canvas_ics' => ['nullable', 'file', 'mimes:ics,txt', 'max:5120', 'required_without:canvas_url'],
            'canvas_url' => ['nullable', 'url', 'required_without:canvas_ics'],

            // Busy optional (upload for MVP)
            'busy_ics' => ['nullable', 'file', 'mimes:ics,txt', 'max:5120'],

            // Settings - horizon is capped by subscription level
            'horizon' => ['nullable', 'integer', 'min:1', 'max:'.$maxHorizon],
            'soft_cap' => ['nullable', 'integer', 'min:1', 'max:24'],
            'hard_cap' => ['nullable', 'integer', 'min:1', 'max:24'],
            'skip_weekends' => ['nullable', 'boolean'],
            'busy_weight' => ['nullable', 'numeric', 'min:0', 'max:10'],
        ];
    }

    public function messages(): array
    {
        $maxHorizon = $this->user()->maxHorizon();

        return [
            'horizon.max' => "Your plan allows a maximum horizon of {$maxHorizon} days. Upgrade to Pro for 30-day planning.",
        ];
    }
}
