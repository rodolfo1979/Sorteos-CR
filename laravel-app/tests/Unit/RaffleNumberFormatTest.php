<?php

namespace Tests\Unit;

use App\Models\Raffle;
use PHPUnit\Framework\TestCase;

class RaffleNumberFormatTest extends TestCase
{
    public function test_ten_thousand_number_raffles_are_formatted_from_0000_to_9999(): void
    {
        $raffle = new Raffle([
            'total_numbers' => 10000,
            'number_width' => 4,
        ]);

        $this->assertSame('0000', $raffle->formatNumber(0));
        $this->assertSame('0999', $raffle->formatNumber(999));
        $this->assertSame('1000', $raffle->formatNumber(1000));
        $this->assertSame('9999', $raffle->formatNumber(9999));
    }
}