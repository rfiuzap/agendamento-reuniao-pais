<?php

namespace App\Http\Controllers\Staff;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SchoolYearController extends Controller
{
    public function index(): View
    {
        $years = SchoolYear::withCount('classes')->orderBy('name')->get();

        return view('staff.years.index', compact('years'));
    }

    public function create(): View
    {
        return view('staff.years.form', ['year' => new SchoolYear(['active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        SchoolYear::create($this->validated($request));

        return redirect()->route('staff.years.index')->with('success', 'Sala/Ano cadastrada.');
    }

    public function edit(SchoolYear $year): View
    {
        return view('staff.years.form', compact('year'));
    }

    public function update(Request $request, SchoolYear $year): RedirectResponse
    {
        $year->update($this->validated($request, $year));

        return redirect()->route('staff.years.index')->with('success', 'Sala/Ano atualizada.');
    }

    public function destroy(SchoolYear $year): RedirectResponse
    {
        if ($year->classes()->exists()) {
            throw new BusinessRuleException('Esta sala/ano possui turmas. Desative-a em vez de excluir.');
        }
        $year->delete();

        return redirect()->route('staff.years.index')->with('success', 'Sala/Ano excluída.');
    }

    private function validated(Request $request, ?SchoolYear $year = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('school_years')->ignore($year)],
        ], [], ['name' => 'nome']);
        $data['active'] = $request->boolean('active');

        return $data;
    }
}
