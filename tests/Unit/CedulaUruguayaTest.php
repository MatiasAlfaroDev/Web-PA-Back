<?php

namespace Tests\Unit;

use App\Rules\CedulaUruguaya;
use PHPUnit\Framework\TestCase;

class CedulaUruguayaTest extends TestCase
{
    private function passes(string $ci): bool
    {
        $failed = false;
        (new CedulaUruguaya)->validate('ci', $ci, function () use (&$failed) {
            $failed = true;
        });

        return ! $failed;
    }

    public function test_valid_cis(): void
    {
        $this->assertTrue($this->passes('12345672'));
        $this->assertTrue($this->passes('1.234.567-2')); // with formatting
        $this->assertTrue($this->passes('46083907'));
        $this->assertTrue($this->passes('1111111'.CedulaUruguaya::checkDigit('1111111')));
    }

    public function test_invalid_cis(): void
    {
        $this->assertFalse($this->passes('12345671')); // wrong check digit
        $this->assertFalse($this->passes('123'));      // too short
        $this->assertFalse($this->passes('123456789')); // too long
        $this->assertFalse($this->passes('abcdefgh'));
    }

    public function test_check_digit(): void
    {
        $this->assertSame(2, CedulaUruguaya::checkDigit('1234567'));
        $this->assertSame(7, CedulaUruguaya::checkDigit('4608390'));
    }
}
