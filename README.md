# Agendamento de Reunião de Pais — Colégio Morumbi

Sistema web para a escola cadastrar reuniões de pais (salas/anos, turmas, professoras) e para os responsáveis agendarem um horário com a professora de cada filho.

**Stack:** Laravel 12 · PHP 8.2+ · MySQL/MariaDB · Blade + CSS/JS próprios (sem etapa de build).

---

## Perfis

| Perfil | Acesso | O que faz |
|---|---|---|
| **Administrador** | `/admin/login` | Tudo: usuários, salas/anos, turmas, professoras, reuniões, agendamentos (alterar/cancelar), dashboard, relatórios, logs e configurações |
| **Coordenadora** | `/admin/login` | Visualiza reuniões, salas, turmas e todas as reservas, com filtros |
| **Professora** | `/admin/login` | Vê somente as próprias turmas: horário, responsável, aluno e status |
| **Responsável** | `/` | Entra com o e-mail + código de 6 dígitos enviado por e-mail, agenda, altera, cancela, agenda outros filhos e adiciona o horário à agenda do celular |

## Instalação local (XAMPP)

```bash
composer install
cp .env.example .env
php artisan key:generate
# crie o banco "reuniao_pais" (utf8mb4_unicode_ci) no phpMyAdmin e ajuste DB_* no .env
php artisan migrate --seed
```

Acesse `http://localhost/Morumbi/agendar_reuniao_de_pais/public/` (ou rode `php artisan serve` e abra `http://127.0.0.1:8000`, ajustando `APP_URL`).

### Dados de demonstração (`php artisan migrate:fresh --seed`)

Todas as contas usam a senha definida em `DatabaseSeeder::DEMO_PASSWORD` (`database/seeders/DatabaseSeeder.php`).

| Usuário | Perfil |
|---|---|
| `admin` | Administrador |
| `coordenadora` | Coordenadora |
| `maria.souza` | Professora (5º Ano A e 5º Ano B) |
| `ana.lima`, `beatriz.rocha`, `fernanda.alves` | Professoras |

- Salas: 4º, 5º e 6º Ano, cada uma com turmas A e B.
- Reunião **liberada** em 15/10/2026, das 08:00 às 13:00 (25 min + 5 min de intervalo), com 7 agendamentos de exemplo.
- Reunião em **rascunho** em 05/12/2026 (não aparece para os pais).
- Responsável de teste com dois filhos já agendados: `joao.silva@exemplo.com`.

**Troque as senhas ou remova as contas de demonstração antes de usar em produção.**

### E-mails em desenvolvimento

Com `MAIL_MAILER=log`, os e-mails (inclusive o código de acesso) são gravados em `storage/logs/laravel.log`.

## Testes

```bash
php artisan test
```

40 testes cobrem geração de horários, concorrência, todas as regras de negócio (RB01–RB21), autenticação por código (hash, validade, tentativas, reenvio), fluxo completo do responsável, permissões por perfil, convite de agenda (.ics) nos e-mails e escape de XSS. Os testes usam SQLite em memória.

## Arquitetura

```
app/
  Services/            Regras de negócio
    AppointmentService   agendar, alterar, cancelar (transação + lock + índices únicos)
    MeetingService       criar/editar reunião e sincronizar horários
    SlotGenerator        cálculo dos horários (duração + intervalo)
    ParentAuthService    código por e-mail (HMAC, validade, tentativas)
    AuditLogger          log das alterações administrativas
  Queries/AppointmentQuery  listagem/filtros compartilhados (admin, coordenação, professora)
  Http/Controllers/{Parent,Staff,Teacher}
  Http/Middleware/     EnsureRole (perfis) e EnsureParentAuthenticated (sessão do responsável)
  Mail/                AccessCodeMail, AppointmentMail (+ resources/views/emails)
  Services/CalendarEvent  evento de agenda (.ics e link do Google Agenda)
database/migrations    estrutura completa do banco
config/reuniao.php     validade do código, tentativas, reenvio e duração da sessão do responsável
```

### Concorrência (item 13)

A reserva ocorre em transação com `SELECT ... FOR UPDATE` no horário. Além disso, o banco garante as regras mesmo se a aplicação for contornada:

- `appointments.active_slot_id` **UNIQUE**: preenchido só enquanto o agendamento está ativo, então um horário tem no máximo um agendamento ativo (RB05). No cancelamento ele volta a `NULL` e o horário é liberado.
- `UNIQUE(student_id, active_meeting_id)`: o mesmo aluno não pode ter dois agendamentos ativos na mesma reunião (RB06).

Se duas pessoas confirmarem o mesmo horário ao mesmo tempo, apenas uma consegue; a outra recebe "Este horário acabou de ser reservado…".

### Segurança

Senhas com bcrypt; código de acesso armazenado como HMAC-SHA256 (nunca em texto), com validade de 10 minutos, uso único, máximo de 5 tentativas, intervalo de 60 s entre envios e limite por hora; *rate limiting* nas rotas de login; sessão regenerada no login; permissões verificadas no backend (middleware `role`); Eloquent/Query Builder com *bindings* (SQL injection); Blade com escape automático (XSS); CSRF em todos os formulários; logs de auditoria em **Relatórios → Logs**.

## Publicação na HostGator (hospedagem compartilhada)

1. **cPanel → Select PHP Version**: escolha **PHP 8.2 ou superior** e ative as extensões `pdo_mysql`, `mbstring`, `openssl`, `zip`, `intl`, `fileinfo`.
2. **cPanel → MySQL Databases**: crie o banco e o usuário e dê todas as permissões.
3. Envie o projeto (sem `node_modules`) para uma pasta **fora** de `public_html`, por exemplo `~/reuniao`.
   - Com SSH: `composer install --no-dev --optimize-autoloader` no servidor.
   - Sem SSH: rode o mesmo comando localmente e envie também a pasta `vendor/`.
4. Aponte o domínio ou subdomínio (cPanel → Domínios) para `~/reuniao/public`.
   *Se não for possível mudar a raiz*, envie o projeto inteiro para a raiz do domínio: o `.htaccess` da raiz já redireciona tudo para `public/` e bloqueia arquivos ocultos como o `.env`.
5. Crie o `.env` no servidor a partir do `.env.example`:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://reuniao.seudominio.com.br
   DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=...
   ```
6. Com SSH, rode:
   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan db:seed --force   # opcional: dados de demonstração
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
   Sem SSH, gere o banco localmente e importe o `.sql` pelo phpMyAdmin.
7. Permissão de escrita em `storage/` e `bootstrap/cache/` (755).

Não é necessário cron nem fila: os e-mails são enviados na hora.

## Configuração de e-mail (SMTP)

Toda a configuração fica centralizada no `.env`. Exemplo com uma conta de e-mail da HostGator:

```
MAIL_MAILER=smtp
MAIL_HOST=mail.seudominio.com.br
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=nao-responda@seudominio.com.br
MAIL_PASSWORD=********
MAIL_FROM_ADDRESS="nao-responda@seudominio.com.br"
MAIL_FROM_NAME="Colégio Morumbi"
```

Depois de alterar, rode `php artisan config:clear` (ou `config:cache` em produção). Os modelos HTML dos e-mails estão em `resources/views/emails/`.

## Personalização

- **Logo:** substitua `public/img/logo.svg`.
- **Nome da escola, contato e instruções aos pais:** Admin → Configurações.
- **Validade do código, tentativas e sessão:** variáveis `AUTH_CODE_*` e `PARENT_SESSION_MINUTES` no `.env` (ver `config/reuniao.php`).
