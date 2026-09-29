<?php

namespace App\Services;

use InvalidArgumentException;

class SlotGenerator
{
    /**
     * Each slot lasts $duration minutes; the next one starts after $duration + $break minutes.
     * A slot is only created if it ends before or exactly at $end.
     *
     * @return array<int, array{start: string, end: string}> times in H:i:s
     */
    public static function generate(string $start, string $end, int $duration, int $break = 0): array
    {
        if ($duration < 1) {
            throw new InvalidArgumentException('Duração inválida.');
        }

        $from = self::toMinutes($start);
        $until = self::toMinutes($end);
        $step = $duration + max(0, $break);
        $slots = [];

        for ($t = $from; $t + $duration <= $until; $t += $step) {
            $slots[] = ['start' => self::toTime($t), 'end' => self::toTime($t + $duration)];
        }

        return $slots;
    }

    public static function normalize(string $time): string
    {
        return self::toTime(self::toMinutes($time));
    }

    private static function toMinutes(string $time): int
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
            throw new InvalidArgumentException("Horário inválido: {$time}");
        }

        return (int) $m[1] * 60 + (int) $m[2];
    }

    private static function toTime(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }
}
