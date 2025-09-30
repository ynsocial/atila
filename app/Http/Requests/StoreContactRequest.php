<?php

namespace App\Http\Requests;

use App\Rules\MinSentences;
use App\Rules\NoInjectionTokens;
use App\Rules\UniqueNameCooldown;
use App\Rules\ValidHumanName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Normalizer;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $inputs = $this->all();
        foreach (['name','email','phone','message'] as $key) {
            if (isset($inputs[$key]) && is_string($inputs[$key])) {
                $val = Normalizer::normalize($inputs[$key], Normalizer::FORM_C);
                $val = Str::of($val)->squish()->__toString();
                $inputs[$key] = $val;
            }
        }
        $this->replace($inputs);
    }

    public function rules(): array
    {
        return [
            'website' => ['nullable','string','max:50'], // honeypot handled in middleware
            'name' => ['required', 'string', 'min:2', 'max:80', new ValidHumanName, new UniqueNameCooldown],
            'email' => ['required', 'string', 'email:rfc,dns', new NoInjectionTokens],
            'message' => ['required', 'string', 'min:30', new MinSentences(config('antispam.sentence_min', 3))],
            'phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please provide your real full name.',
            'email.email' => 'Please provide a valid email address.',
            'message.min' => 'Please write a more detailed message.',
        ];
    }
}

