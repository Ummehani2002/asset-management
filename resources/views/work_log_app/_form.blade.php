@php
    $isTimeAdmin = $isAdmin ?? auth()->user()?->isTimeManagementAdmin() ?? false;
    $todayTotals = $todayTotals ?? ['total_hours' => 0, 'job_count' => 0];
    $openTickets = $openTickets ?? collect();
    $continueTicket = $continueTicket ?? null;
    $isEdit = ! empty($record);
    $linkedTicket = $record?->workTicket;
    $defaultLogType = old('log_type', $continueTicket ? 'continue' : 'new');
    $workDate = old('job_card_date', optional($record?->job_card_date)->format('Y-m-d') ?? date('Y-m-d'));
    $defaultStart = old('start_time_hour', $record && $record->start_time ? $record->start_time->format('H:i') : now()->format('H:i'));
    $defaultEnd = old('end_time_hour', $record && $record->end_time ? $record->end_time->format('H:i') : now()->addMinutes(30)->format('H:i'));
    $selectedTicketId = old('work_ticket_id', $continueTicket?->id ?? $linkedTicket?->id);
    $ticketFieldsLocked = $isEdit ? (bool) $linkedTicket : ($defaultLogType === 'continue');
@endphp

<form action="{{ $action }}" method="POST" id="workLogForm" autocomplete="off">
    @csrf
    <input type="hidden" name="_from_app" value="1">

    <div class="info-bar mb-3">
        <div>
            <small class="text-muted d-block">Employee</small>
            <strong>{{ $employeeName }}</strong>
        </div>
    </div>

    @unless($isTimeAdmin)
    <div class="alert alert-light border mb-3 py-2">
        <small class="text-muted d-block">Logged today (before this job)</small>
        <strong class="text-success">{{ \App\Models\TimeManagement::formatDuration($todayTotals['total_hours']) }}</strong>
        <small class="text-muted"> · {{ $todayTotals['job_count'] }} job(s) so far</small>
    </div>
    @endunless

    @unless($isEdit)
    <div class="mb-3">
        <label class="form-label">Log Type <span class="text-danger">*</span></label>
        <div class="d-flex gap-3">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="log_type" id="log_type_new" value="new" {{ $defaultLogType === 'new' ? 'checked' : '' }}>
                <label class="form-check-label" for="log_type_new">New ticket</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="log_type" id="log_type_continue" value="continue"
                       {{ $defaultLogType === 'continue' ? 'checked' : '' }} {{ $openTickets->isEmpty() ? 'disabled' : '' }}>
                <label class="form-check-label" for="log_type_continue">Continue ticket</label>
            </div>
        </div>
    </div>

    <div id="continue_ticket_section" class="mb-3" style="{{ $defaultLogType === 'continue' ? '' : 'display:none;' }}">
        <label class="form-label">Open Ticket <span class="text-danger">*</span></label>
        <select name="work_ticket_id" id="work_ticket_id" class="form-select">
            <option value="">Choose a ticket...</option>
            @foreach($openTickets as $ticket)
                <option value="{{ $ticket->id }}"
                        data-ticket-number="{{ $ticket->ticket_number }}"
                        data-category="{{ $ticket->category }}"
                        data-task="{{ $ticket->task_description }}"
                        data-location="{{ $ticket->site_location }}"
                        {{ (string) $selectedTicketId === (string) $ticket->id ? 'selected' : '' }}>
                    {{ $ticket->ticket_number }} — {{ $ticket->site_location }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Reuse the same ticket for every visit until work is done.</small>
    </div>
    @endunless

    @if($isEdit && $linkedTicket)
    <div class="alert alert-light border mb-3 py-2">
        <div class="small"><strong>{{ $linkedTicket->ticket_number }}</strong> · {{ $linkedTicket->site_location }}</div>
        <div class="small text-muted">Total: {{ \App\Models\TimeManagement::formatDuration($linkedTicket->totalDurationHours()) }} · {{ $linkedTicket->visitCount() }} visit(s)</div>
    </div>
    @endif

    <div class="mb-3">
        <label class="form-label">Ticket Number <span class="text-danger">*</span></label>
        <input type="text" name="ticket_number" id="ticket_number" class="form-control ticket-field" maxlength="50"
               value="{{ old('ticket_number', $continueTicket?->ticket_number ?? $record?->ticket_number ?? '') }}"
               placeholder="e.g. INC-12345"
               {{ $ticketFieldsLocked ? 'readonly' : 'required' }}>
    </div>

    <div class="mb-3">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="category" id="category" class="form-select ticket-field" {{ $ticketFieldsLocked ? 'disabled' : 'required' }}>
            @php $category = old('category', $continueTicket?->category ?? $record?->category ?? \App\Models\TimeManagement::DEFAULT_CATEGORY); @endphp
            <option value="End User Support" {{ $category === 'End User Support' ? 'selected' : '' }}>End User Support</option>
            <option value="Infrastructure" {{ $category === 'Infrastructure' ? 'selected' : '' }}>Infrastructure</option>
            <option value="Network" {{ $category === 'Network' ? 'selected' : '' }}>Network</option>
            <option value="Hardware" {{ $category === 'Hardware' ? 'selected' : '' }}>Hardware</option>
            <option value="Software" {{ $category === 'Software' ? 'selected' : '' }}>Software</option>
            <option value="Other" {{ $category === 'Other' ? 'selected' : '' }}>Other</option>
        </select>
        @if($ticketFieldsLocked)
            <input type="hidden" name="category" value="{{ old('category', $continueTicket?->category ?? $linkedTicket?->category ?? $record?->category ?? \App\Models\TimeManagement::DEFAULT_CATEGORY) }}">
        @endif
    </div>

    <div class="mb-3">
        <label class="form-label">Work Date <span class="text-danger">*</span></label>
        <input type="date" name="job_card_date" class="form-control" required value="{{ $workDate }}">
    </div>

    <div class="mb-3">
        <label class="form-label">Site / Location <span class="text-danger">*</span></label>
        <input type="text" name="site_location" id="site_location" class="form-control ticket-field" maxlength="100"
               value="{{ old('site_location', $continueTicket?->site_location ?? $record?->site_location ?? '') }}"
               placeholder="e.g. Head Office"
               {{ $ticketFieldsLocked ? 'readonly' : 'required' }}>
    </div>

    <div class="mb-3">
        <label class="form-label">Task Description <span class="text-danger">*</span>
            <small class="text-muted fw-normal">(max 50)</small>
        </label>
        <input type="text" name="task_description" id="task_description" class="form-control ticket-field"
               maxlength="50"
               value="{{ old('task_description', $continueTicket?->task_description ?? $record?->task_description ?? '') }}"
               placeholder="e.g. Fix laptop WiFi issue"
               {{ $ticketFieldsLocked ? 'readonly' : 'required' }}>
        <div class="d-flex justify-content-end mt-1">
            <small class="text-muted"><span id="task_desc_count">0</span>/50</small>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label">Start Time <span class="text-danger">*</span></label>
            <input type="time" name="start_time_hour" id="start_time_hour" class="form-control" required value="{{ $defaultStart }}">
        </div>
        <div class="col-6">
            <label class="form-label">End Time <span class="text-danger">*</span></label>
            <input type="time" name="end_time_hour" id="end_time_hour" class="form-control" required value="{{ $defaultEnd }}">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Time Taken</label>
        <input type="text" id="time_spent_display" class="form-control bg-light" readonly value="0 min">
        <small class="text-muted">Shown as hours and minutes</small>
    </div>

    @unless($isTimeAdmin)
    <div class="alert alert-info py-2 mb-3">
        <small id="day_total_hint">After saving, your total for this day will update automatically.</small>
    </div>
    @endunless

    <div class="mb-3">
        <label class="form-label">Action Taken / Resolution</label>
        <input type="text" name="action_taken" class="form-control" maxlength="150"
               value="{{ old('action_taken', $record?->action_taken ?? '') }}"
               placeholder="What was done on this visit?">
    </div>

    <div class="mb-3">
        <label class="form-label">Ticket Status <span class="text-danger">*</span></label>
        @php $status = old('status', request('status', $record?->ticketStatus() ?? 'pending')); if ($status === 'in_progress') $status = 'pending'; @endphp
        <select name="status" class="form-select" required>
            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Open — more visits expected</option>
            <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed — close ticket</option>
        </select>
    </div>

    <div class="mb-4">
        <label class="form-label">Remarks</label>
        <input type="text" name="remarks" class="form-control" maxlength="150"
               value="{{ old('remarks', $record?->remarks ?? '') }}"
               placeholder="Optional notes">
    </div>

    <button type="submit" class="btn-app">
        <i class="bi bi-check-circle me-1"></i>
        {{ $record ? 'Update Work Log' : 'Save Work Log' }}
    </button>
    @if($record)
        <a href="{{ route('worklog.index') }}" class="btn btn-outline-secondary w-100 mt-2" style="border-radius: 12px; padding: 12px;">
            Back to My Jobs
        </a>
    @endif
</form>

@push('scripts')
<script src="{{ asset('js/format-work-duration.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.querySelector('input[name="job_card_date"]');
    const startInput = document.getElementById('start_time_hour');
    const endInput = document.getElementById('end_time_hour');
    const timeSpentDisplay = document.getElementById('time_spent_display');
    const taskDescInput = document.getElementById('task_description');
    const taskDescCount = document.getElementById('task_desc_count');
    const dayTotalHint = document.getElementById('day_total_hint');
    const todayLoggedBase = {{ json_encode((float) ($todayTotals['total_hours'] ?? 0)) }};
    const logTypeNew = document.getElementById('log_type_new');
    const logTypeContinue = document.getElementById('log_type_continue');
    const continueSection = document.getElementById('continue_ticket_section');
    const workTicketSelect = document.getElementById('work_ticket_id');
    const ticketNumberInput = document.getElementById('ticket_number');
    const categorySelect = document.getElementById('category');
    const siteLocationInput = document.getElementById('site_location');

    function setTicketFieldsLocked(locked) {
        [ticketNumberInput, siteLocationInput, taskDescInput].forEach(function (el) {
            if (!el) return;
            el.readOnly = locked;
            el.required = !locked;
        });
        if (categorySelect) {
            categorySelect.disabled = locked;
            categorySelect.required = !locked;
        }
        if (workTicketSelect) workTicketSelect.required = locked;
    }

    function applyContinueTicketSelection() {
        if (!workTicketSelect || !workTicketSelect.value) return;
        const option = workTicketSelect.selectedOptions[0];
        if (!option) return;
        if (ticketNumberInput) ticketNumberInput.value = option.dataset.ticketNumber || '';
        if (categorySelect) categorySelect.value = option.dataset.category || '';
        if (siteLocationInput) siteLocationInput.value = option.dataset.location || '';
        if (taskDescInput) taskDescInput.value = option.dataset.task || '';
    }

    function updateLogTypeUi() {
        const isContinue = logTypeContinue && logTypeContinue.checked;
        if (continueSection) continueSection.style.display = isContinue ? '' : 'none';
        setTicketFieldsLocked(isContinue);
        if (isContinue) applyContinueTicketSelection();
    }

    if (logTypeNew) logTypeNew.addEventListener('change', updateLogTypeUi);
    if (logTypeContinue) logTypeContinue.addEventListener('change', updateLogTypeUi);
    if (workTicketSelect) workTicketSelect.addEventListener('change', applyContinueTicketSelection);
    updateLogTypeUi();
    applyContinueTicketSelection();

    function updateTaskCount() {
        taskDescCount.textContent = (taskDescInput.value || '').length;
    }

    function updateTimeSpent() {
        if (!dateInput.value || !startInput.value || !endInput.value) {
            timeSpentDisplay.value = '0 min';
            if (dayTotalHint) dayTotalHint.textContent = 'After saving, your total for this day will update automatically.';
            return;
        }
        const start = new Date(dateInput.value + 'T' + startInput.value);
        const end = new Date(dateInput.value + 'T' + endInput.value);
        if (end <= start) {
            timeSpentDisplay.value = '0 min';
            if (dayTotalHint) dayTotalHint.textContent = 'End time must be after start time.';
            return;
        }
        const hours = (end - start) / (1000 * 60 * 60);
        timeSpentDisplay.value = formatWorkDuration(hours);
        if (dayTotalHint) {
            const dayTotal = todayLoggedBase + hours;
            dayTotalHint.textContent = 'Time taken: ' + formatWorkDuration(hours) + '. Total working hours: ' + formatWorkDuration(dayTotal) + '.';
        }
    }

    taskDescInput.addEventListener('input', updateTaskCount);
    dateInput.addEventListener('change', updateTimeSpent);
    startInput.addEventListener('change', updateTimeSpent);
    endInput.addEventListener('change', updateTimeSpent);
    updateTaskCount();
    updateTimeSpent();
});
</script>
@endpush
