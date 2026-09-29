{{-- Expects: $rows (appointments from AppointmentQuery) --}}
@php($canManage = auth()->user()->isAdmin())
<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Data</th>
            <th>Horário</th>
            <th>Sala</th>
            <th>Turma</th>
            <th>Professora</th>
            <th>Responsável</th>
            <th>Aluno</th>
            <th>Status</th>
            @if($canManage)<th class="text-right">Ações</th>@endif
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $a)
            <tr>
                <td class="nowrap">{{ \Carbon\Carbon::parse($a->meeting_date)->format('d/m/Y') }}</td>
                <td class="nowrap"><strong>{{ $a->start_time ? substr($a->start_time, 0, 5) : '—' }}</strong></td>
                <td>{{ $a->school_year_name ?? '—' }}</td>
                <td class="nowrap">{{ $a->class_name ?? '—' }}</td>
                <td>{{ $a->teacher_name ?? '—' }}</td>
                <td>{{ $a->responsible_name }}<div class="small muted">{{ $a->responsible_email }}</div></td>
                <td>{{ $a->student_name }}</td>
                <td><span class="badge {{ $a->isActive() ? 'badge-green' : 'badge-red' }}">{{ $a->statusLabel() }}</span></td>
                @if($canManage)
                    <td class="text-right">
                        @if($a->isActive())
                            <div class="actions" style="justify-content:flex-end">
                                <a href="{{ route('staff.appointments.edit', $a) }}" class="btn btn-light btn-sm">Alterar</a>
                                <form method="POST" action="{{ route('staff.appointments.cancel', $a) }}" data-confirm="Deseja realmente cancelar este agendamento?">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Cancelar</button>
                                </form>
                            </div>
                        @endif
                    </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="9" class="empty">Nenhum agendamento encontrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
