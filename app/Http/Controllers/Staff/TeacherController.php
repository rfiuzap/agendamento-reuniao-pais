<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(): View
    {
        $teachers = User::teachers()->with('classes')->orderBy('name')->get();

        return view('staff.teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        return view('staff.teachers.form', [
            'teacher' => new User(['active' => true]),
            'classes' => SchoolClass::with('schoolYear', 'teacher')->orderBy('name')->get(),
            'selected' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $teacher = User::create($data['user'] + ['role' => User::ROLE_TEACHER]);
            $this->syncClasses($teacher, $data['class_ids']);
            AuditLogger::log('created', 'teacher', $teacher->id, ['username' => $teacher->username, 'classes' => $data['class_ids']]);
        });

        return redirect()->route('staff.teachers.index')->with('success', 'Professora cadastrada.');
    }

    public function edit(User $teacher): View
    {
        abort_unless($teacher->role === User::ROLE_TEACHER, 404);

        return view('staff.teachers.form', [
            'teacher' => $teacher,
            'classes' => SchoolClass::with('schoolYear', 'teacher')->orderBy('name')->get(),
            'selected' => $teacher->classes()->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === User::ROLE_TEACHER, 404);
        $data = $this->validated($request, $teacher);

        DB::transaction(function () use ($teacher, $data) {
            $teacher->update($data['user']);
            $this->syncClasses($teacher, $data['class_ids']);
            AuditLogger::log('updated', 'teacher', $teacher->id, [
                'username' => $teacher->username,
                'active' => $teacher->active,
                'classes' => $data['class_ids'],
                'password_changed' => isset($data['user']['password']),
            ]);
        });

        return redirect()->route('staff.teachers.index')->with('success', 'Professora atualizada.');
    }

    private function syncClasses(User $teacher, array $classIds): void
    {
        SchoolClass::where('teacher_id', $teacher->id)->whereNotIn('id', $classIds)->update(['teacher_id' => null]);
        SchoolClass::whereIn('id', $classIds)->update(['teacher_id' => $teacher->id]);
    }

    private function validated(Request $request, ?User $teacher = null): array
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'username' => mb_strtolower(trim((string) $request->input('username'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($teacher)],
            'username' => ['required', 'alpha_dash', 'max:60', Rule::unique('users')->ignore($teacher)],
            'password' => [$teacher ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'class_ids' => ['array'],
            'class_ids.*' => ['integer', Rule::exists('classes', 'id')],
        ], [], ['name' => 'nome', 'username' => 'usuário', 'password' => 'senha']);

        $user = [
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'active' => $request->boolean('active'),
        ];
        if (! empty($data['password'])) {
            $user['password'] = $data['password'];
        }

        return ['user' => $user, 'class_ids' => array_map('intval', $data['class_ids'] ?? [])];
    }
}
