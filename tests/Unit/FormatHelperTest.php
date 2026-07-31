<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Helpers\FormatHelper;

class FormatHelperTest extends TestCase
{
    public function test_formats_numbers_correctly(): void
    {
        $this->assertEquals('0', FormatHelper::short(0));
        $this->assertEquals('467', FormatHelper::short(467));
        $this->assertEquals('1k', FormatHelper::short(1000));
        $this->assertEquals('25.7k', FormatHelper::short(25684));
        $this->assertEquals('247.3k', FormatHelper::short(247278));
        $this->assertEquals('4.5M', FormatHelper::short(4498682));
        $this->assertEquals('1.2B', FormatHelper::short(1200000000));
    }
}
