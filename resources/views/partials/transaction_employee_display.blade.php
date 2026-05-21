@php
    $transaction = $transaction ?? null;
    $employee = $transaction?->employee;
@endphp
<td>
    @if($employee && !empty($employee->employee_id))
        <strong>{{ $employee->employee_id }}</strong>
    @else
        <span class="text-muted">N/A</span>
    @endif
</td>
<td>
    @if(($transaction->transaction_type ?? '') === 'system_maintenance')
        {{ $employee->name ?? 'N/A' }}<br>
        <small class="text-muted">(Maintenance)</small>
    @elseif($employee)
        {{ $employee->name ?? 'N/A' }}
    @else
        {{ $transaction->project_name ?? 'N/A' }}
    @endif
</td>
