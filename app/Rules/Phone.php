<?php

namespace App\Rules;

use App\Enums\Country;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Phone implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * The fixed country code, or name of the field containing the country code.
     */
    protected string $countryOrField;

    /**
     * Create a new rule instance.
     */
    public function __construct(string $countryOrField = 'any')
    {
        $this->countryOrField = $countryOrField;
    }

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('The :attribute must be a string.'));

            return;
        }

        // Clean value of spaces, hyphens, parentheses, dots
        $cleanValue = preg_replace('/[\s\-().]/', '', $value);

        // Determine country code
        $countryCode = $this->resolveCountryCode();

        if (! $this->validateFormat($cleanValue, $countryCode)) {
            $fail(__('The :attribute must be a valid phone number format.'));
        }
    }

    /**
     * Resolve the country code to use for validation.
     */
    protected function resolveCountryCode(): string
    {
        // Check if $countryOrField exists as a key in $data
        if (array_key_exists($this->countryOrField, $this->data)) {
            return strtoupper((string) $this->data[$this->countryOrField]);
        }

        return strtoupper($this->countryOrField);
    }

    /**
     * Validate the format based on country code.
     */
    protected function validateFormat(string $value, string $countryCode): bool
    {
        $country = Country::tryFrom($countryCode);

        $pattern = $country ? $country->regex() : '/^\+?[1-9]\d{6,14}$/';

        return (bool) preg_match($pattern, $value);
    }
}
