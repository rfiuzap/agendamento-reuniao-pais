<div class="grid-2">
    <div class="field">
        <label for="name">Nome</label>
        <input type="text" id="name" name="name" required maxlength="150" value="{{ old('name', $user->name) }}" class="@error('name') is-invalid @enderror">
    </div>
    <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required maxlength="190" value="{{ old('email', $user->email) }}" class="@error('email') is-invalid @enderror">
    </div>
    <div class="field">
        <label for="username">Usuário</label>
        <input type="text" id="username" name="username" required maxlength="60" value="{{ old('username', $user->username) }}"
               autocomplete="off" class="@error('username') is-invalid @enderror">
        <div class="hint">Letras, números, hífen e sublinhado.</div>
    </div>
    @isset($roles)
        <div class="field">
            <label for="role">Perfil</label>
            <select id="role" name="role" required>
                @foreach($roles as $key => $label)
                    <option value="{{ $key }}" @selected(old('role', $user->role) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endisset
    <div class="field">
        <label for="password">Senha {{ $user->exists ? '(deixe em branco para manter)' : '' }}</label>
        <input type="password" id="password" name="password" {{ $user->exists ? '' : 'required' }} minlength="8"
               autocomplete="new-password" class="@error('password') is-invalid @enderror">
    </div>
    <div class="field">
        <label for="password_confirmation">Confirmar senha</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
    </div>
</div>
<label class="check"><input type="checkbox" name="active" value="1" @checked(old('active', $user->active))> Ativo (pode acessar o sistema)</label>
