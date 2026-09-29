<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Setting;
use App\Services\CalendarEvent;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AppointmentMail extends Mailable
{
    public const TITLES = [
        'confirmed' => 'Agendamento confirmado',
        'updated' => 'Agendamento alterado',
        'cancelled' => 'Agendamento cancelado',
    ];

    public function __construct(public Appointment $appointment, public string $type)
    {
        $this->appointment->loadMissing(['meeting', 'timeSlot.schoolClass.teacher']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::TITLES[$this->type].' - '.Setting::get('school_name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.appointment', with: [
            'title' => self::TITLES[$this->type],
            'googleUrl' => $this->type !== 'cancelled' && $this->appointment->timeSlot
                ? (new CalendarEvent($this->appointment))->googleUrl()
                : null,
        ]);
    }

    /** Calendar invite: e-mail apps offer to add, update or remove the event. */
    public function attachments(): array
    {
        if (! $this->appointment->timeSlot) {
            return [];
        }

        $event = new CalendarEvent($this->appointment);
        $method = $this->type === 'cancelled' ? 'CANCEL' : 'PUBLISH';

        return [
            Attachment::fromData(fn () => $event->ics($method), $event->filename())
                ->withMime('text/calendar; charset=utf-8; method='.$method),
        ];
    }
}
