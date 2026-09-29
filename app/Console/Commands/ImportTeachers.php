<?php

namespace App\Console\Commands;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Imports rooms, classes and teachers from lines "Sala - Turma - Professora - e-mail"
 * (file argument or standard input). Safe to run again: existing records are reused.
 */
class ImportTeachers extends Command
{
    protected $signature = 'app:importar-professoras
        {arquivo? : Arquivo com uma linha por turma (sem ele, lê a entrada padrão)}
        {--senha=morumbi : Senha inicial das professoras novas (troca obrigatória no 1º acesso)}
        {--simular : Mostra o que seria feito, sem gravar nada}';

    protected $description = 'Cadastra salas, turmas e professoras a partir de uma lista';

    public function handle(): int
    {
        $lines = $this->readLines();
        if (! $lines) {
            $this->error('Nenhuma linha recebida.');

            return self::FAILURE;
        }

        $rows = [];
        $emails = [];
        foreach ($lines as $n => $line) {
            // Separator is a hyphen with a space on at least one side, so "reserva-area" in e-mails is kept.
            $parts = array_map('trim', preg_split('/\s+-\s*|\s*-\s+/u', $line));
            if (count($parts) !== 4 || ! filter_var($parts[3], FILTER_VALIDATE_EMAIL)) {
                $this->warn('Linha '.($n + 1).' ignorada (formato esperado "Sala - Turma - Professora - e-mail"): '.$line);
                continue;
            }
            [$year, $class, $teacher, $email] = $parts;
            $email = mb_strtolower($email);
            if (isset($emails[$email])) {
                $this->warn("Linha ".($n + 1)." ignorada: o e-mail {$email} já foi usado para {$emails[$email]} nesta lista.");
                continue;
            }
            $emails[$email] = $teacher;
            $rows[] = ['year' => $year, 'class' => $year.' '.$class, 'teacher' => $teacher, 'email' => $email];
        }

        $simulate = (bool) $this->option('simular');
        $report = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $report[] = $this->importRow($row);
            }
            $simulate ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->table(['Sala', 'Turma', 'Professora', 'Login', 'Situação'], $report);
        $this->info($simulate
            ? 'Simulação: nada foi gravado. Rode sem --simular para cadastrar.'
            : 'Importação concluída. Senha inicial das professoras novas: "'.$this->option('senha').'" (troca obrigatória no 1º acesso).');

        return self::SUCCESS;
    }

    private function importRow(array $row): array
    {
        $year = SchoolYear::firstOrCreate(['name' => $row['year']], ['active' => true]);

        $user = User::where('email', $row['email'])->first();
        if ($user && $user->role !== User::ROLE_TEACHER) {
            return [$row['year'], $row['class'], $row['teacher'], $user->username, 'IGNORADA: e-mail já é de um usuário que não é professora'];
        }

        $status = [];
        if (! $user) {
            $user = User::create([
                'name' => $row['teacher'],
                'email' => $row['email'],
                'username' => $this->uniqueUsername(Str::before($row['email'], '@')),
                'password' => $this->option('senha'),
                'role' => User::ROLE_TEACHER,
                'active' => true,
                'must_change_password' => true,
            ]);
            $status[] = 'professora criada';
        } else {
            $status[] = 'professora já existia';
        }

        $class = SchoolClass::where('name', $row['class'])->first();
        if (! $class) {
            SchoolClass::create(['school_year_id' => $year->id, 'name' => $row['class'], 'teacher_id' => $user->id, 'active' => true]);
            $status[] = 'turma criada';
        } elseif ($class->teacher_id !== $user->id || $class->school_year_id !== $year->id) {
            $class->update(['school_year_id' => $year->id, 'teacher_id' => $user->id]);
            $status[] = 'turma atualizada';
        } else {
            $status[] = 'turma já existia';
        }

        return [$row['year'], $row['class'], $row['teacher'], $user->username, implode(', ', $status)];
    }

    private function uniqueUsername(string $base): string
    {
        $base = Str::slug($base, '.') ?: 'professora';
        $name = $base;
        for ($i = 2; User::where('username', $name)->exists(); $i++) {
            $name = $base.$i;
        }

        return $name;
    }

    /** @return list<string> */
    private function readLines(): array
    {
        $file = $this->argument('arquivo');
        $content = $file ? @file_get_contents($file) : stream_get_contents(STDIN);

        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $content))));
    }
}
