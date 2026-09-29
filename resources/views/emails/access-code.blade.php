@extends('emails.layout', ['title' => 'Código de acesso'])

@section('content')
    <p style="margin:0 0 12px;">Olá,</p>
    <p style="margin:0 0 20px;">Use o código abaixo para acessar o agendamento da reunião de pais:</p>
    <p style="margin:0 0 20px;text-align:center;">
        <span style="display:inline-block;padding:14px 28px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;font-size:30px;font-weight:bold;letter-spacing:8px;color:#1e3a8a;">{{ $code }}</span>
    </p>
    <p style="margin:0 0 12px;">O código é válido por <strong>{{ $ttl }} minutos</strong> e pode ser usado uma única vez.</p>
    <p style="margin:0;color:#64748b;font-size:13px;">Se você não solicitou este código, ignore este e-mail.</p>
@endsection
