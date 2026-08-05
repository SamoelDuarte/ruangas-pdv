<?php

namespace Tests\Unit;

use App\Services\Tracker\TrackerTcpMessageIngestor;
use PHPUnit\Framework\TestCase;

class TrackerTcpMessageIngestorTest extends TestCase
{
    public function test_parse_gtstt_exposes_ignition_and_voltage(): void
    {
        $ingestor = new TrackerTcpMessageIngestor();
        $method = new \ReflectionMethod($ingestor, 'parseMessage');
        $method->setAccessible(true);

        $payload = '+RESP:GTSTT,80201C0200,866714080397374,GV30CAU,22,1,0.0,0,847.5,-46.776556,-23.702243,20260805124126,0724,0003,7999,0B1191D4,00,20260805124127,20CC$';

        $result = $method->invoke($ingestor, $payload);

        $this->assertSame('GTSTT', $result['packet_type']);
        $this->assertTrue($result['ignition']);
        $this->assertEqualsWithDelta(14.13, $result['tensao_veiculo'], 0.01);
    }

    public function test_parse_gtigl_exposes_ignition_and_voltage(): void
    {
        $ingestor = new TrackerTcpMessageIngestor();
        $method = new \ReflectionMethod($ingestor, 'parseMessage');
        $method->setAccessible(true);

        $payload = '+RESP:GTIGL,80201C0200,866714080397374,GV30CAU,,00,1,1,0.0,0,847.5,-46.776556,-23.702243,20260805120059,0724,0003,7999,0B1191D4,00,0.0,20260805120100,20C9$';

        $result = $method->invoke($ingestor, $payload);

        $this->assertSame('GTIGL', $result['packet_type']);
        $this->assertTrue($result['ignition']);
        $this->assertEqualsWithDelta(14.13, $result['tensao_veiculo'], 0.01);
    }
}
