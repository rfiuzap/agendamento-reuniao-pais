<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Setting;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\MeetingService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Password shared by every demo account. Change it (or the accounts) before going to production. */
    public const DEMO_PASSWORD = 'Morumbi@2026';

    public function run(): void
    {
        // Demo booking e-mails must not reach real inboxes.
        config(['mail.default' => 'array']);

        Setting::put(Setting::DEFAULTS + ['contact_email' => 'secretaria@colegiomorumbi.com.br']);

        $user = fn (string $name, string $username, string $role) => User::create([
            'name' => $name,
            'email' => $username.'@colegiomorumbi.com.br',
            'username' => $username,
            'password' => self::DEMO_PASSWORD,
            'role' => $role,
            'active' => true,
        ]);

        $user('Administrador', 'admin', User::ROLE_ADMIN);
        $user('Carla Mendes', 'coordenadora', User::ROLE_COORDINATOR);

        $teachers = [
            '4º Ano A' => $user('Ana Paula Lima', 'ana.lima', User::ROLE_TEACHER),
            '4º Ano B' => $user('Beatriz Rocha', 'beatriz.rocha', User::ROLE_TEACHER),
            '5º Ano A' => $user('Maria Souza', 'maria.souza', User::ROLE_TEACHER),
            '6º Ano A' => $user('Fernanda Alves', 'fernanda.alves', User::ROLE_TEACHER),
        ];
        // Maria Souza teaches two classes to show multi-class teachers.
        $teachers['5º Ano B'] = $teachers['5º Ano A'];
        $teachers['6º Ano B'] = $teachers['6º Ano A'];

        $classIds = [];
        foreach (['4º Ano', '5º Ano', '6º Ano'] as $yearName) {
            $year = SchoolYear::create(['name' => $yearName, 'active' => true]);
            foreach (['A', 'B'] as $letter) {
                $name = "{$yearName} {$letter}";
                $classIds[$name] = SchoolClass::create([
                    'school_year_id' => $year->id,
                    'name' => $letter, // the room name is not repeated in the class name

                    'teacher_id' => $teachers[$name]->id,
                    'active' => true,
                ])->id;
            }
        }

        $meetings = app(MeetingService::class);
        $main = $meetings->save(new Meeting, [
            'name' => 'Reunião de Pais - 2º Semestre',
            'date' => '2026-10-15',
            'start_time' => '08:00',
            'end_time' => '13:00',
            'duration_minutes' => 25,
            'break_minutes' => 5,
            'status' => 'published',
        ], array_values($classIds));

        $meetings->save(new Meeting, [
            'name' => 'Reunião de Pais - Encerramento do Ano',
            'date' => '2026-12-05',
            'start_time' => '08:00',
            'end_time' => '11:00',
            'duration_minutes' => 20,
            'break_minutes' => 5,
            'status' => 'draft',
        ], [$classIds['5º Ano A'], $classIds['5º Ano B']]);

        $bookings = [
            ['joao.silva@exemplo.com', 'João Silva', 'Pedro Silva', '5º Ano B', '08:00'],
            ['joao.silva@exemplo.com', 'João Silva', 'Maria Silva', '4º Ano A', '09:00'],
            ['carla.santos@exemplo.com', 'Carla Santos', 'Lucas Santos', '5º Ano A', '08:00'],
            ['roberto.dias@exemplo.com', 'Roberto Dias', 'Júlia Dias', '5º Ano A', '08:30'],
            ['patricia.gomes@exemplo.com', 'Patrícia Gomes', 'Gabriel Gomes', '6º Ano A', '10:00'],
            ['marcos.oliveira@exemplo.com', 'Marcos Oliveira', 'Sofia Oliveira', '4º Ano B', '11:30'],
            ['renata.costa@exemplo.com', 'Renata Costa', 'Enzo Costa', '6º Ano B', '12:30'],
        ];

        $service = app(AppointmentService::class);
        foreach ($bookings as [$email, $responsible, $student, $class, $time]) {
            $slot = TimeSlot::where('meeting_id', $main->id)
                ->where('class_id', $classIds[$class])
                ->where('start_time', $time.':00')
                ->firstOrFail();
            $service->book($email, $responsible, $student, $slot->id);
        }
    }
}
