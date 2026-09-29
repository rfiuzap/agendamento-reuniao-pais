<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AccessCodeMail extends Mailable
{
    public function __construct(public string $code)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Seu código de acesso - '.Setting::get('school_name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.access-code', with: [
            'ttl' => config('reuniao.code_ttl_minutes'),
        ]);
    }
}
