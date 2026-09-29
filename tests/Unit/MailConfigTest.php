<?php

namespace Tests\Unit;

use Tests\TestCase;

class MailConfigTest extends TestCase
{
    public function test_smtp_ehlo_uses_site_domain_instead_of_localhost_ip(): void
    {
        $this->assertSame('localhost', config('mail.mailers.smtp.local_domain')); // APP_URL in phpunit.xml

        $transport = app('mail.manager')->createSymfonyTransport(
            array_merge(config('mail.mailers.smtp'), ['local_domain' => 'agendamento.reserva-area.com.br'])
        );
        $this->assertSame('agendamento.reserva-area.com.br', $transport->getLocalDomain());
    }
}
