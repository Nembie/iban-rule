<?php

namespace Nembie\IbanRule;

use Closure;
use Illuminate\Support\Facades\Lang;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIban implements ValidationRule
{
    /**
     * The country rules.
     *
     * @var array
     */
    protected static $countryRules;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->checkIBAN($value))
            $this->error($fail);
    }

    /**
     * Validate IBAN.
     */
    protected function checkIBAN(string $iban): bool
    {
        // IBAN must contain only uppercase letters and digits
        if (!preg_match('/^[A-Z0-9]+$/', $iban))
            return false;

        $countryRules = $this->getCountryRules();

        $countryCode = substr($iban, 0, 2);
        $countryObj = $countryRules['sepa'][$countryCode] ?? $countryRules['not_sepa'][$countryCode] ?? null;

        if ($countryObj === null)
            return false;

        // Get validation rules
        $rules = array_map(fn($attr) => $attr[1], $countryObj);

        // Validate IBAN structure against country rules
        $tempIban = $iban;
        $ibanLength = 0;

        foreach ($rules as $rule) {
            $numbers = intval(preg_replace('/[^0-9]/', '', $rule));
            $letter = preg_replace('/[^a-zA-Z]/', '', $rule);
            $checkString = substr($tempIban, 0, $numbers);
            $ibanLength += $numbers;

            if (($letter === 'a' && !ctype_alpha($checkString)) || ($letter === 'n' && !ctype_digit($checkString)))
                return false;

            $tempIban = substr($tempIban, $numbers);
        }

        if ($ibanLength !== strlen($iban))
            return false;

        // Validate MOD-97 checksum (ISO 7064)
        return $this->validateMod97($iban);
    }

    /**
     * Validate IBAN checksum using MOD-97 algorithm (ISO 7064).
     */
    protected function validateMod97(string $iban): bool
    {
        // Move the first 4 characters to the end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        // Replace each letter with two digits (A=10, B=11, ..., Z=35)
        $numericString = '';
        for ($i = 0, $len = strlen($rearranged); $i < $len; $i++) {
            $char = $rearranged[$i];
            $numericString .= ctype_alpha($char)
                ? (ord($char) - ord('A') + 10)
                : $char;
        }

        // Compute remainder digit by digit to avoid big integer overflow
        $remainder = 0;
        for ($i = 0, $len = strlen($numericString); $i < $len; $i++) {
            $remainder = ($remainder * 10 + (int) $numericString[$i]) % 97;
        }

        return $remainder === 1;
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
     */
    protected function error(Closure $fail)
    {
        $message = 'The :attribute is not a valid IBAN.';

        try {
            if (Lang::has('validation.iban')) {
                $message = Lang::get('validation.iban');
            }
        } catch (\RuntimeException) {
            // Lang facade not available outside Laravel
        }

        return $fail($message);
    }
}
