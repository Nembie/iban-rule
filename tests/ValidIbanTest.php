<?php

use Nembie\IbanRule\ValidIban;
use PHPUnit\Framework\TestCase;

class ValidIbanTest extends TestCase
{
    /**
     * Test a valid IBAN.
     * 
     * @return void
     */
    public function testValidIban(): void
    {
        $ibanValidator = new ValidIban();
    
        $validIban = 'DE02100500000024290661';
    
        $validatorMock = $this->getMockBuilder('Illuminate\Validation\Validator')
            ->disableOriginalConstructor()
            ->getMock();
    
        $validatorMock->expects($this->never())
            ->method('errors');
    
        // Pass a Closure as the third argument to the validate method
        $ibanValidator->validate('iban', $validIban, function () {
            // The validation should not fail, so this function should not be called.
            $this->fail('Validation failed for a valid IBAN.');
        });
    }

    /**
     * Test an invalid IBAN.
     * 
     * @return void
     */
    public function testInvalidIban(): void
    {
        $ibanValidator = new ValidIban();
        
        $invalidIban = 'ABCD12345667';
    
        $validatorMock = $this->getMockBuilder('Illuminate\Validation\Validator')
            ->disableOriginalConstructor()
            ->getMock();
    
        $validatorMock->expects($this->once())
            ->method('errors')
            ->willReturn('The :attribute is not a valid IBAN.');
    
        $ibanValidator->setValidator($validatorMock);
        
        // Pass a Closure as the third argument to the validate method
        $ibanValidator->validate('iban', $invalidIban, function ($message) {
            // The validation should fail, so this function should be called.
            $this->assertEquals('The :attribute is not a valid IBAN.', $message);
        });
    }

    /**
     * Test an IBAN with white space.
     * 
     * @return void
     */
    public function testIbanWithWhiteSpace(): void
    {
        $ibanValidator = new ValidIban();
        
        $ibanWithWhiteSpace = 'DE02 1005 0000 0024 2906 61';
    
        $validatorMock = $this->getMockBuilder('Illuminate\Validation\Validator')
            ->disableOriginalConstructor()
            ->getMock();
    
        $validatorMock->expects($this->once())
            ->method('errors')
            ->willReturn('The :attribute is not a valid IBAN.');
    
        $ibanValidator->setValidator($validatorMock);
        
        // Pass a Closure as the third argument to the validate method
        $ibanValidator->validate('iban', $ibanWithWhiteSpace, function ($message) {
            // The validation should fail, so this function should be called.
            $this->assertEquals('The :attribute is not a valid IBAN.', $message);
        });
    }

    /**
     * Test an IBAN with special characters.
     * 
     * @return void
     */
    public function testIbanWithSpecialCharacters(): void
    {
        $ibanValidator = new ValidIban();
        
        $ibanWithSpecialCharacters = 'DE02!1005 0000 0024 2906 61';
    
        $validatorMock = $this->getMockBuilder('Illuminate\Validation\Validator')
            ->disableOriginalConstructor()
            ->getMock();
    
        $validatorMock->expects($this->once())
            ->method('errors')
            ->willReturn('The :attribute is not a valid IBAN.');
    
        $ibanValidator->setValidator($validatorMock);
        
        // Pass a Closure as the third argument to the validate method
        $ibanValidator->validate('iban', $ibanWithSpecialCharacters, function ($message) {
            // The validation should fail, so this function should be called.
            $this->assertEquals('The :attribute is not a valid IBAN.', $message);
        });
    }
}
