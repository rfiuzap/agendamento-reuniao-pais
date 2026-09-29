<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Setting;
use Carbon\Carbon;

/** Builds the appointment as a calendar event (.ics file and Google Calendar link). */
class CalendarEvent
{
    public function __construct(private Appointment $appointment)
    {
        $this->appointment->loadMissing(['meeting', 'timeSlot.schoolClass.teacher']);
    }

    public function title(): string
    {
        return 'Reunião de Pais - '.$this->appointment->student_name;
    }

    public function description(): string
    {
        $class = $this->appointment->timeSlot?->schoolClass;

        return implode("\n", array_filter([
            $this->appointment->meeting->name,
            'Aluno: '.$this->appointment->student_name,
            $class ? 'Turma: '.$class->fullName() : null,
            $class?->teacher ? 'Professora: '.$class->teacher->name : null,
            'Para alterar ou cancelar: '.route('parent.login'),
        ]));
    }

    public function start(): Carbon
    {
        return $this->at($this->appointment->timeSlot->start_time);
    }

    public function end(): Carbon
    {
        return $this->at($this->appointment->timeSlot->end_time);
    }

    /** @param  string  $method  REQUEST to add/update, CANCEL to remove from the calendar */
    public function ics(string $method = 'PUBLISH'): string
    {
        $cancel = $method === 'CANCEL';
        $utc = fn (Carbon $d) => $d->copy()->utc()->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.self::escape(Setting::get('school_name')).'//Reuniao de Pais//PT-BR',
            'CALSCALE:GREGORIAN',
            'METHOD:'.$method,
            'BEGIN:VEVENT',
            'UID:agendamento-'.$this->appointment->id.'@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'reuniao'),
            'DTSTAMP:'.$utc(now()),
            'SEQUENCE:'.$this->appointment->updated_at->timestamp,
            'DTSTART:'.$utc($this->start()),
            'DTEND:'.$utc($this->end()),
            'SUMMARY:'.self::escape($this->title()),
            'DESCRIPTION:'.self::escape($this->description()),
            'LOCATION:'.self::escape(Setting::get('school_name')),
            'STATUS:'.($cancel ? 'CANCELLED' : 'CONFIRMED'),
        ];

        if (! $cancel) {
            array_push($lines, 'BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:'.self::escape($this->title()), 'TRIGGER:-PT1H', 'END:VALARM');
        }

        array_push($lines, 'END:VEVENT', 'END:VCALENDAR');

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    public function googleUrl(): string
    {
        $utc = fn (Carbon $d) => $d->copy()->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $this->title(),
            'dates' => $utc($this->start()).'/'.$utc($this->end()),
            'details' => $this->description(),
            'location' => Setting::get('school_name'),
            'ctz' => config('app.timezone'),
        ]);
    }

    public function filename(): string
    {
        return 'reuniao-de-pais-'.$this->appointment->id.'.ics';
    }

    private function at(string $time): Carbon
    {
        return Carbon::parse($this->appointment->meeting->date->format('Y-m-d').' '.$time, config('app.timezone'));
    }

    private static function escape(?string $text): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], (string) $text);
    }

    /** RFC 5545: lines longer than 75 octets must be folded. */
    private static function fold(string $line): string
    {
        $out = '';
        while (strlen($line) > 75) {
            $cut = 75;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--; // do not split a UTF-8 character
            }
            $out .= substr($line, 0, $cut)."\r\n ";
            $line = substr($line, $cut);
        }

        return $out.$line;
    }
}
