<?php

namespace App\Http\Requests;

use App\Enums\LeadGoal;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['name', 'email'] as $field) {
            if (is_string($this->input($field))) {
                $merge[$field] = trim($this->input($field));
            }
        }

        foreach (['phone', 'message'] as $field) {
            if (is_string($this->input($field)) && trim($this->input($field)) === '') {
                $merge[$field] = null;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\s().\-]+$/'],
            'goal' => ['required', Rule::enum(LeadGoal::class)],
            'message' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable'],
            'started_at' => ['nullable', 'integer'],
            'visitor_id' => ['nullable'],
            'utm_source' => ['nullable'],
            'utm_medium' => ['nullable'],
            'utm_campaign' => ['nullable'],
            'utm_term' => ['nullable'],
            'utm_content' => ['nullable'],
            'referrer' => ['nullable'],
            'landing_path' => ['nullable'],
        ];
    }

    public function isSpamTrap(): bool
    {
        $website = $this->input('website');

        if (is_string($website) && trim($website) !== '') {
            return true;
        }

        $started = $this->input('started_at');

        if (is_numeric($started) && (now()->getTimestamp() - (int) $started) < 2) {
            return true;
        }

        return false;
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        throw new HttpResponseException(
            redirect('/#contact')->withErrors($validator)->withInput()
        );
    }
}
