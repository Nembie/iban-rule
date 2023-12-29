<?php

namespace Nembie\IbanRule;

use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidIban implements ValidationRule
{
    /**
     * The country rules.
     *
     * @var array
     */
    protected static $countryRules;

    /**
     * The validator instance.
     *
     * @var Validator
     */
    protected $validator;

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->validateIban($value)){
            $this->validator ? $this->validator->errors() : null;
            $fail('The :attribute is not a valid IBAN.');
        }
    }

    /**
     * Set the validator instance.
     *
     * @param  Validator  $validator
     * @return void
     */
    public function setValidator($validator): void
    {
        $this->validator = $validator;
    }

    /**
     * Validate IBAN.
     *
     * @param  string  $iban
     * @return bool
     */
    protected function validateIban($iban): bool
    {
        // Check if IBAN contains white space or special characters
        if (preg_match('/\s|[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $iban)) {
            return false;
        }

        $countryRules = $this->loadCountryRules();

        $countryCode = substr($iban, 0, 2);
        $countryObj = $countryRules['sepa'][$countryCode] ?? $countryRules['not_sepa'][$countryCode] ?? null;

        if ($countryObj === null) {
            return false;
        }

        // Get validation rules
        $rules = array_map(fn($attr) => $attr[1], $countryObj);

        // Validate IBAN against rules
        $tempIban = $iban;
        $ibanLength = 0;

        foreach ($rules as $rule) {
            $numbers = intval(preg_replace('/[^0-9]/', '', $rule));
            $letter = preg_replace('/[^a-zA-Z]/', '', $rule);
            $checkString = substr($tempIban, 0, $numbers);
            $ibanLength += $numbers;

            // Check if the string part is of the correct type
            if (($letter === 'a' && !ctype_alpha($checkString)) || ($letter === 'n' && !ctype_digit($checkString))) {
                return false;
            }

            $tempIban = substr($tempIban, $numbers);
        }

        return $ibanLength == strlen($iban);
    }

    /**
     * Load country rules.
     *
     * @return array
     */
    protected function loadCountryRules(): array
    {
        if (self::$countryRules === null) {
            self::$countryRules = json_decode(
                file_get_contents(
                    dirname(__FILE__, 2) . '/resources/json/countries.json'
                ),
                true
            );
        }

        return self::$countryRules;
    }
}
