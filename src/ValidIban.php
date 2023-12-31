<?php

namespace Nembie\IbanRule;

use Closure;
use Illuminate\Support\Facades\Lang;
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
        if (!$this->checkIBAN($value))
            $this->error($fail);
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
    protected function checkIBAN($iban): bool
    {
        // Check if IBAN contains white space or special characters
        if (preg_match('/\s|[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $iban))
            return false;

        $countryRules = $this->getCountryRules();

        $countryCode = substr($iban, 0, 2);
        $countryObj = $countryRules['sepa'][$countryCode] ?? $countryRules['not_sepa'][$countryCode] ?? null;

        if ($countryObj === null)
            return false;

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
            if (($letter === 'a' && !ctype_alpha($checkString)) || ($letter === 'n' && !ctype_digit($checkString)))
                return false;

            $tempIban = substr($tempIban, $numbers);
        }

        return $ibanLength == strlen($iban);
    }

    /**
     * Get country rules. If not already loaded, load them.
     *
     * @return array
     */
    protected function getCountryRules(): array
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

    /**
     * Get the validation error message.
     *
     * @param  Closure  $fail
     */
    protected function error(Closure $fail)
    {
        $this->validator && $this->validator->errors();

        return $fail(
            (!class_exists('Lang') || !Lang::has('validation.iban')) ?
                'The :attribute is not a valid IBAN.'
                : Lang::get('validation.iban')
        );
    }
}