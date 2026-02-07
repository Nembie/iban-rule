<?php

use Nembie\IbanRule\ValidIban;
use PHPUnit\Framework\TestCase;

class ValidIbanTest extends TestCase
{
    public function testValidIban(): void
    {
        $ibanValidator = new ValidIban();
        $failed = false;

        $ibanValidator->validate('iban', 'DE02100500000024290661', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, 'Validation should not have failed for a valid IBAN.');
    }

    public function testInvalidIban(): void
    {
        $ibanValidator = new ValidIban();
        $failed = false;

        $ibanValidator->validate('iban', 'ABCD12345667', function ($message) use (&$failed) {
            $failed = true;
            $this->assertEquals('The :attribute is not a valid IBAN.', $message);
        });

        $this->assertTrue($failed, 'Validation should have failed for an invalid IBAN.');
    }

    public function testIbanWithWhiteSpace(): void
    {
        $ibanValidator = new ValidIban();
        $failed = false;

        $ibanValidator->validate('iban', 'DE02 1005 0000 0024 2906 61', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed, 'Validation should have failed for IBAN with whitespace.');
    }

    public function testIbanWithSpecialCharacters(): void
    {
        $ibanValidator = new ValidIban();
        $failed = false;

        $ibanValidator->validate('iban', 'DE02!100500000024290661', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed, 'Validation should have failed for IBAN with special characters.');
    }

    public function testIbanWithInvalidChecksum(): void
    {
        $ibanValidator = new ValidIban();
        $failed = false;

        // Structurally valid German IBAN but with wrong checksum (00 instead of 02)
        $ibanValidator->validate('iban', 'DE00100500000024290661', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed, 'Validation should have failed for IBAN with invalid MOD-97 checksum.');
    }

    public function testIbanWithLowercaseCharacters(): void
    {
        $ibanValidator = new ValidIban();
        $failed = false;

        $ibanValidator->validate('iban', 'de02100500000024290661', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, 'Lowercase IBAN should be accepted after normalization.');
    }
}
