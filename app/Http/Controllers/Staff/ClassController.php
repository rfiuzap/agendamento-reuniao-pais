<?php

namespace App\Http\Controllers\Staff;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View
    {
        $classes = SchoolClass::with(['schoolYear', 'teacher'])
            ->when($request->query('school_year_id'), fn ($q, $v) => $q->where('school_year_id', $v))
            ->orderBy('name')
            ->get();

        return view('staff.classes.index', [
            'classes' => $classes,
            'years' => SchoolYear::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('staff.classes.form', $this->formData(new SchoolClass([
            'active' => true,
            'school_year_id' => $request->query('school_year_id'),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        SchoolClass::create($this->validated($request));

        return redirect()->route('staff.classes.index')->with('success', 'Turma cadastrada.');
    }

    public function edit(SchoolClass $class): View
    {
        return view('staff.classes.form', $this->formData($class));
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        $class->update($this->validated($request, $class));

        return redirect()->route('staff.classes.index')->with('success', 'Turma atualizada.');
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        if ($class->meetings()->exists()) {
            throw new BusinessRuleException('Esta turma participa de reuniões. Desative-a em vez de excluir.');
        }
        $class->delete();

        return redirect()->route('staff.classes.index')->with('success', 'Turma excluída.');
    }

    private function formData(SchoolClass $class): array
    {
        return [
            'class' => $class,
            'years' => SchoolYear::orderBy('name')->get(),
            'teachers' => User::teachers()->where('active', true)->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?SchoolClass $class = null): array
    {
        $data = $request->validate([
            'school_year_id' => ['required', 'integer', Rule::exists('school_years', 'id')],
            'name' => ['required', 'string', 'max:80', Rule::unique('classes')->ignore($class)],
            'teacher_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', User::ROLE_TEACHER)],
        ], [], ['school_year_id' => 'sala/ano', 'name' => 'nome', 'teacher_id' => 'professora']);
        $data['active'] = $request->boolean('active');

        return $data;
    }
}
