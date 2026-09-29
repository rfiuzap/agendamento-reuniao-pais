<?php

namespace Tests\Unit;

use App\Services\SlotGenerator;
use PHPUnit\Framework\TestCase;

class SlotGeneratorTest extends TestCase
{
    public function test_generates_slots_with_duration_plus_break(): void
    {
        $slots = SlotGenerator::generate('08:00', '10:30', 25, 5);

        $this->assertSame(['08:00:00', '08:30:00', '09:00:00', '09:30:00', '10:00:00'], array_column($slots, 'start'));
        $this->assertSame('08:25:00', $slots[0]['end']);
    }

    public function test_last_slot_must_end_before_meeting_end(): void
    {
        $slots = SlotGenerator::generate('08:00', '09:20', 25, 5);

        $this->assertSame(['08:00:00', '08:30:00'], array_column($slots, 'start'));
    }

    public function test_spec_example_from_8_to_13(): void
    {
        $slots = SlotGenerator::generate('08:00', '13:00', 25, 5);

        $this->assertCount(10, $slots);
        $this->assertSame('12:30:00', end($slots)['start']);
    }
}
