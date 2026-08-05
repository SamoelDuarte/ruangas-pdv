<?php

namespace Tests\Unit;

use App\Services\Tracker\TrackerTcpMessageIngestor;
use PHPUnit\Framework\TestCase;

class TrackerAddressStayLocationTest extends TestCase
{
    public function test_it_resets_stay_when_the_house_number_changes_on_the_same_street(): void
    {
        $service = new TrackerTcpMessageIngestor();

        $method = new \ReflectionMethod(TrackerTcpMessageIngestor::class, 'isSameStayLocation');
        $method->setAccessible(true);

        $sameStay = $method->invoke(
            $service,
            'Rua Nova Providência, 4',
            'Rua Nova Providência, 100',
            -23.702243,
            -46.776556,
            -23.702243,
            -46.776556,
        );

        $this->assertFalse($sameStay);
    }

    public function test_it_keeps_the_same_stay_when_the_position_is_still_close_to_the_same_house(): void
    {
        $service = new TrackerTcpMessageIngestor();

        $method = new \ReflectionMethod(TrackerTcpMessageIngestor::class, 'isSameStayLocation');
        $method->setAccessible(true);

        $sameStay = $method->invoke(
            $service,
            'Rua Nova Providência, 4',
            'Rua Nova Providência, 4',
            -23.702243,
            -46.776556,
            -23.702243 + 0.0001,
            -46.776556 + 0.0001,
        );

        $this->assertTrue($sameStay);
    }
}
