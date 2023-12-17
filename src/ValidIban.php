<?php

namespace Nembie\IbanRule;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIban implements ValidationRule
{
    /**
     * The country rules.
     *
     * @var array
     */
    static $countryRules = null;

    /**
     * The validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->validateIban($value)){
            $this->validator->errors();
            $fail('The :attribute is not a valid IBAN.');
        }
    }

    /**
     * Set the validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     */
    public function setValidator($validator): void
    {
        $this->validator = $validator;
    }

    /**
     * Validate IBAN
     * @param string $iban
     * @return bool
     */
    protected function validateIban($iban): bool
    {
        $hasError = false;

        // Check if IBAN contains white space or special characters
        if (preg_match('/\s|[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $iban))
            return false;
    
        // Get the validation rules by country
        if (self::$countryRules === null) {
            $path = dirname(__FILE__, 2) . '/resources/json/countries.json';
            $json = file_get_contents($path);
            $countryRules = json_decode($json, true);
        }
        
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
            if (
                ($letter === 'a' && !ctype_alpha($checkString))
                || ($letter === 'n' && !ctype_digit($checkString))
            ) {
                $hasError = true;
                break;
            }
    
            $tempIban = substr($tempIban, $numbers);
        }
    
        if (!$hasError && $ibanLength != strlen($iban))
            $hasError = true;
    
        return !$hasError;
    }    
}