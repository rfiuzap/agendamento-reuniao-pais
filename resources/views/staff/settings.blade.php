@extends('layouts.staff')

@section('title', 'Configurações')

@section('content')
    <div class="page-header"><h1>Configurações</h1></div>

    <form method="POST" action="{{ route('staff.settings.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="card">
            <h2>Escola</h2>
            <div class="grid-2">
                <div class="field">
                    <label for="school_name">Nome da escola</label>
                    <input type="text" id="school_name" name="school_name" required maxlength="120" value="{{ old('school_name', $settings['school_name']) }}">
                </div>
                <div class="field">
                    <label for="contact_email">E-mail de contato</label>
                    <input type="email" id="contact_email" name="contact_email" maxlength="190" value="{{ old('contact_email', $settings['contact_email']) }}">
                </div>
                <div class="field">
                    <label for="contact_phone">Telefone de contato</label>
                    <input type="text" id="contact_phone" name="contact_phone" maxlength="40" value="{{ old('contact_phone', $settings['contact_phone']) }}">
                </div>
            </div>
            <div class="field">
                <label for="parent_instructions">Instruções exibidas aos responsáveis</label>
                <textarea id="parent_instructions" name="parent_instructions" maxlength="1000">{{ old('parent_instructions', $settings['parent_instructions']) }}</textarea>
            </div>
            <div class="grid-2">
                <div class="field">
                    <label for="brand_logo">Logo</label>
                    <div class="logo-field">
                        <img src="{{ $brandLogoUrl }}" alt="Logo atual" class="logo-preview logo-preview-wide">
                        <div>
                            <input type="file" id="brand_logo" name="brand_logo" accept="image/png,image/jpeg,image/webp"
                                   class="@error('brand_logo') is-invalid @enderror">
                            <div class="hint">Aparece no topo das páginas, na página inicial e na tela do código. PNG, JPG ou WEBP, até 1 MB. Pode ser horizontal.</div>
                            @if($settings['brand_logo'])
                                <label class="check mt-1"><input type="checkbox" name="remove_brand_logo" value="1"> Remover logo (usa o ícone)</label>
                            @endif
                        </div>
                    </div>
                    @error('brand_logo')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="logo">Ícone</label>
                    <div class="logo-field">
                        <img src="{{ $logoUrl }}" alt="Ícone atual" class="logo-preview">
                        <div>
                            <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp"
                                   class="@error('logo') is-invalid @enderror">
                            <div class="hint">Ícone da aba do navegador, do aplicativo no celular e do menu da equipe. Prefira imagem quadrada.</div>
                            @if($settings['logo'])
                                <label class="check mt-1"><input type="checkbox" name="remove_logo" value="1"> Voltar ao ícone padrão</label>
                            @endif
                        </div>
                    </div>
                    @error('logo')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <button type="submit" class="btn">Salvar</button>
        </div>
    </form>

    <div class="card">
        <h2>E-mail (SMTP)</h2>
        <p class="muted">Por segurança, as credenciais SMTP ficam no arquivo <code>.env</code> do servidor e não são editáveis pela interface.</p>
        <dl class="summary">
            <dt>Envio</dt><dd>{{ $mail['mailer'] === 'log' ? 'Log (desenvolvimento — e-mails gravados em storage/logs)' : strtoupper($mail['mailer']) }}</dd>
            <dt>Servidor</dt><dd>{{ $mail['host'] }}:{{ $mail['port'] }}</dd>
            <dt>Remetente</dt><dd>{{ $mail['from'] }}</dd>
        </dl>
    </div>
@endsection
