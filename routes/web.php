<?php

use App\Http\Controllers\Parent\AuthController as ParentAuth;
use App\Http\Controllers\Parent\BookingController;
use App\Http\Controllers\Parent\PortalController;
use App\Http\Controllers\Staff\AppointmentController;
use App\Http\Controllers\Staff\AuthController as StaffAuth;
use App\Http\Controllers\Staff\ClassController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\MeetingController;
use App\Http\Controllers\Staff\ReportController;
use App\Http\Controllers\Staff\SchoolYearController;
use App\Http\Controllers\Staff\SettingController;
use App\Http\Controllers\Staff\TeacherController;
use App\Http\Controllers\Staff\UserController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboard;
use Illuminate\Support\Facades\Route;

// Responsible (parent) access by e-mail code
Route::get('/', [ParentAuth::class, 'showEmail'])->name('parent.login');
Route::post('/acesso', [ParentAuth::class, 'sendCode'])->middleware('throttle:10,1')->name('parent.code.send');
Route::get('/acesso/codigo', [ParentAuth::class, 'showCode'])->name('parent.code');
Route::post('/acesso/codigo', [ParentAuth::class, 'verify'])->middleware('throttle:15,1')->name('parent.code.verify');
Route::post('/acesso/reenviar', [ParentAuth::class, 'resend'])->middleware('throttle:5,1')->name('parent.code.resend');
Route::post('/sair', [ParentAuth::class, 'logout'])->name('parent.logout');

Route::middleware('parent')->group(function () {
    Route::get('/minha-area', [PortalController::class, 'index'])->name('parent.home');

    Route::get('/agendar', [BookingController::class, 'meetings'])->name('parent.booking.meetings');
    Route::get('/agendar/{meeting}', [BookingController::class, 'details'])->name('parent.booking.details');
    Route::post('/agendar/{meeting}', [BookingController::class, 'storeDetails'])->name('parent.booking.details.store');
    Route::get('/agendar/{meeting}/horarios', [BookingController::class, 'slots'])->name('parent.booking.slots');
    Route::post('/agendar/{meeting}/horarios', [BookingController::class, 'selectSlot'])->name('parent.booking.slots.select');
    Route::get('/agendar/{meeting}/resumo', [BookingController::class, 'summary'])->name('parent.booking.summary');
    Route::post('/agendar/{meeting}/confirmar', [BookingController::class, 'confirm'])->name('parent.booking.confirm');

    Route::get('/agendamentos/{appointment}/sucesso', [PortalController::class, 'success'])->name('parent.appointments.success');
    Route::get('/agendamentos/{appointment}/agenda.ics', [PortalController::class, 'calendar'])->name('parent.appointments.calendar');
    Route::get('/agendamentos/{appointment}/alterar', [PortalController::class, 'edit'])->name('parent.appointments.edit');
    Route::post('/agendamentos/{appointment}/alterar', [PortalController::class, 'update'])->name('parent.appointments.update');
    Route::get('/agendamentos/{appointment}/cancelar', [PortalController::class, 'confirmCancel'])->name('parent.appointments.cancel');
    Route::post('/agendamentos/{appointment}/cancelar', [PortalController::class, 'cancel'])->name('parent.appointments.cancel.store');
});

// Staff (admin, coordinator, teacher)
Route::prefix('admin')->name('staff.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [StaffAuth::class, 'show'])->name('login');
        Route::post('/login', [StaffAuth::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    });
    Route::post('/logout', [StaffAuth::class, 'logout'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'role:admin,coordinator'])->group(function () {
        Route::get('/agendamentos', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/reunioes', [MeetingController::class, 'index'])->name('meetings.index');
        Route::get('/reunioes/{meeting}', [MeetingController::class, 'show'])->whereNumber('meeting')->name('meetings.show');
        Route::get('/salas', [SchoolYearController::class, 'index'])->name('years.index');
        Route::get('/turmas', [ClassController::class, 'index'])->name('classes.index');
    });

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('reunioes', MeetingController::class)
            ->parameters(['reunioes' => 'meeting'])->except(['index', 'show'])->names('meetings');
        Route::resource('salas', SchoolYearController::class)
            ->parameters(['salas' => 'year'])->except(['index', 'show'])->names('years');
        Route::resource('turmas', ClassController::class)
            ->parameters(['turmas' => 'class'])->except(['index', 'show'])->names('classes');
        Route::resource('professores', TeacherController::class)
            ->parameters(['professores' => 'teacher'])->except(['show', 'destroy'])->names('teachers');
        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'user'])->except(['show', 'destroy'])->names('users');

        Route::get('/agendamentos/{appointment}/editar', [AppointmentController::class, 'edit'])->name('appointments.edit');
        Route::put('/agendamentos/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
        Route::post('/agendamentos/{appointment}/cancelar', [AppointmentController::class, 'cancel'])->name('appointments.cancel');

        Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/relatorios/logs', [ReportController::class, 'logs'])->name('reports.logs');
        Route::get('/configuracoes', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/configuracoes', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::middleware(['auth', 'role:teacher'])->group(function () {
        Route::get('/professora', [TeacherDashboard::class, 'index'])->name('teacher.dashboard');
    });
});
