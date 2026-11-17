<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class StageThreeMoneyTest extends TestCase
{
    public function test_decimal_money_uses_integer_cents_and_consistent_rounding(): void
    {
        $this->assertSame(47900, Money::cents('479.00'));
        $this->assertSame(11, Money::cents('0.105'));
        $this->assertSame('479.00', Money::decimal(47900));
        $this->assertSame('0.01', Money::decimal(1));
    }

    public function test_percentage_discount_is_precise_in_cents(): void
    {
        $this->assertSame(1250, Money::percentageDiscount(10000, '12.50'));
        $this->assertSame(3333, Money::percentageDiscount(9999, '33.33'));
    }
}
