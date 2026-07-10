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

    <div class="row g-3 mb-2">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-4 p-3 rounded border bg-light">
                <div>
                    <small class="text-muted d-block">Employee Name</small>
                    <strong class="fs-6">{{ $employeeName }}</strong>
                </div>
                <div class="vr d-none d-md-block"></div>
                @unless($isTimeAdmin)
                <div>
                    <small class="text-muted d-block">Logged today (before this job)</small>
                    <strong class="fs-6 text-success" id="today_logged_display">{{ \App\Models\TimeManagement::formatDuration($todayTotals['total_hours']) }}</strong>
                    <small class="text-muted d-block">{{ $todayTotals['job_count'] }} job(s) so far</small>
                </div>
                @endunless
            </div>
        </div>
    </div>

    @unless($isEdit)
    <div class="row g-3 mb-2">
        <div class="col-12">
            <label class="form-label">Log Type <span class="text-danger">*</span></label>
            <div class="d-flex flex-wrap gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="log_type" id="log_type_new" value="new"
                           {{ $defaultLogType === 'new' ? 'checked' : '' }}>
                    <label class="form-check-label" for="log_type_new">New ticket</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="log_type" id="log_type_continue" value="continue"
                           {{ $defaultLogType === 'continue' ? 'checked' : '' }} {{ $openTickets->isEmpty() ? 'disabled' : '' }}>
                    <label class="form-check-label" for="log_type_continue">Continue open ticket</label>
                </div>
            </div>
            @if($openTickets->isEmpty())
                <small class="text-muted">No open tickets yet. Start with a new ticket number.</small>
            @endif
        </div>
    </div>

    <div id="continue_ticket_section" class="row g-3 mb-2" style="{{ $defaultLogType === 'continue' ? '' : 'display:none;' }}">
        <div class="col-md-8">
            <label class="form-label">Select Open Ticket <span class="text-danger">*</span></label>
            <select name="work_ticket_id" id="work_ticket_id" class="form-select">
                <option value="">Choose a ticket...</option>
                @foreach($openTickets as $ticket)
                    <option value="{{ $ticket->id }}"
                            data-ticket-number="{{ $ticket->ticket_number }}"
                            data-category="{{ $ticket->category }}"
                            data-task="{{ $ticket->task_description }}"
                            data-location="{{ $ticket->site_location }}"
                            data-visits="{{ $ticket->visits_count }}"
                            {{ (string) $selectedTicketId === (string) $ticket->id ? 'selected' : '' }}>
                        {{ $ticket->ticket_number }} — {{ $ticket->site_location }} ({{ $ticket->visits_count }} visit{{ $ticket->visits_count === 1 ? '' : 's' }})
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Same ticket number is reused for every visit until the work is completed.</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">Ticket Total So Far</label>
            <input type="text" id="ticket_total_display" class="form-control bg-white" readonly value="—">
        </div>
    </div>
    @endunless

    @if($isEdit && $linkedTicket)
    <div class="alert alert-light border mb-3">
        <div class="d-flex flex-wrap gap-4">
            <div><small class="text-muted d-block">Ticket</small><strong>{{ $linkedTicket->ticket_number }}</strong></div>
            <div><small class="text-muted d-block">Location</small><strong>{{ $linkedTicket->site_location }}</strong></div>
            <div><small class="text-muted d-block">Total on ticket</small><strong>{{ \App\Models\TimeManagement::formatDuration($linkedTicket->totalDurationHours()) }}</strong></div>
            <div><small class="text-muted d-block">Visits</small><strong>{{ $linkedTicket->visitCount() }}</strong></div>
        </div>
        <a href="{{ route('time.ticket.show', $linkedTicket->id) }}" class="small">View all visits on this ticket</a>
    </div>
    @endif

    <div class="row g-3">
        <div class="col-md-4" id="ticket_number_field">
            <label class="form-label">Ticket Number <span class="text-danger">*</span></label>
            <input type="text" name="ticket_number" id="ticket_number" class="form-control ticket-field"
                   maxlength="50"
                   value="{{ old('ticket_number', $continueTicket?->ticket_number ?? $record?->ticket_number ?? '') }}"
                   placeholder="e.g. INC-12345"
                   {{ $ticketFieldsLocked ? 'readonly' : 'required' }}>
        </div>

        <div class="col-md-4">
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <select name="category" id="category" class="form-select ticket-field" {{ $ticketFieldsLocked ? 'disabled' : 'required' }}>
                @php $category = old('category', $record?->category ?? \App\Models\TimeManagement::DEFAULT_CATEGORY); @endphp
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

        <div class="col-md-4">
            <label class="form-label">Work Date <span class="text-danger">*</span></label>
            <input type="date" name="job_card_date" class="form-control" required value="{{ $workDate }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Site / Location <span class="text-danger">*</span></label>
            <input type="text" name="site_location" id="site_location" class="form-control ticket-field" maxlength="100"
                   value="{{ old('site_location', $continueTicket?->site_location ?? $record?->site_location ?? '') }}"
                   placeholder="e.g. Head Office"
                   {{ $ticketFieldsLocked ? 'readonly' : 'required' }}>
        </div>

        <div class="col-12">
            <label class="form-label">Task Description <span class="text-danger">*</span>
                <small class="text-muted">(max 50 characters)</small>
            </label>
            <input type="text" name="task_description" id="task_description" class="form-control ticket-field"
                   maxlength="50"
                   value="{{ old('task_description', $continueTicket?->task_description ?? $record?->task_description ?? '') }}"
                   placeholder="Short task summary, e.g. Fix laptop WiFi issue"
                   {{ $ticketFieldsLocked ? 'readonly' : 'required' }}>
            <div class="d-flex justify-content-end mt-1">
                <small class="text-muted"><span id="task_desc_count">0</span>/50</small>
            </div>
        </div>

        <div class="col-md-{{ $isTimeAdmin ? '3' : '4' }}">
            <label class="form-label">Start Time <span class="text-danger">*</span></label>
            <input type="time" name="start_time_hour" id="start_time_hour" class="form-control" required
                   value="{{ $defaultStart }}">
        </div>

        <div class="col-md-{{ $isTimeAdmin ? '3' : '4' }}">
            <label class="form-label">End Time <span class="text-danger">*</span></label>
            <input type="time" name="end_time_hour" id="end_time_hour" class="form-control" required
                   value="{{ $defaultEnd }}">
        </div>

        <div class="col-md-{{ $isTimeAdmin ? '2' : '4' }}">
            <label class="form-label">Time Taken</label>
            <input type="text" id="time_spent_display" class="form-control bg-white" readonly value="0 min">
            <small class="text-muted">Shown as hours and minutes</small>
        </div>

        @if($isTimeAdmin)
        <div class="col-md-2">
            <label class="form-label">Standard</label>
            <input type="text" class="form-control bg-white" readonly value="8 hrs (480 min)">
        </div>

        <div class="col-md-2">
            <label class="form-label">Overtime</label>
            <input type="text" id="overtime_hint" class="form-control bg-white" readonly value="On save">
        </div>
        @else
        <div class="col-md-12">
            <div class="alert alert-info py-2 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                <span id="day_total_hint">After saving, your total for this day will update automatically.</span>
            </div>
        </div>
        @endif

        <div class="col-md-8">
            <label class="form-label">Action Taken / Resolution</label>
            <input type="text" name="action_taken" class="form-control" maxlength="150"
                   value="{{ old('action_taken', $record?->action_taken ?? '') }}"
                   placeholder="What was done to resolve the task?">
        </div>

        <div class="col-md-4">
            <label class="form-label">Ticket Status <span class="text-danger">*</span></label>
            @php $status = old('status', $record?->ticketStatus() ?? 'pending'); if ($status === 'in_progress') $status = 'pending'; @endphp
            <select name="status" class="form-select" required>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Open — more visits expected</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed — close this ticket</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Remarks</label>
            <input type="text" name="remarks" class="form-control" maxlength="150"
                   value="{{ old('remarks', $record?->remarks ?? '') }}"
                   placeholder="Optional notes">
        </div>
    </div>

    <div class="d-flex gap-2 mt-4 pt-3 border-top">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>{{ $record ? 'Update Work Log' : 'Save Work Log' }}
        </button>
        <a href="{{ route('time.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

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
    const taskDescriptionInput = document.getElementById('task_description');
    const ticketTotalDisplay = document.getElementById('ticket_total_display');

    function setTicketFieldsLocked(locked) {
        [ticketNumberInput, siteLocationInput, taskDescriptionInput].forEach(function (el) {
            if (!el) return;
            el.readOnly = locked;
            el.required = !locked;
        });
        if (categorySelect) {
            categorySelect.disabled = locked;
            categorySelect.required = !locked;
        }
        if (ticketNumberInput) {
            ticketNumberInput.required = !locked || !workTicketSelect;
        }
        if (workTicketSelect) {
            workTicketSelect.required = locked;
        }
    }

    function applyContinueTicketSelection() {
        if (!workTicketSelect || !workTicketSelect.value) {
            if (ticketTotalDisplay) ticketTotalDisplay.value = '—';
            return;
        }

        const option = workTicketSelect.selectedOptions[0];
        if (!option) return;

        if (ticketNumberInput) ticketNumberInput.value = option.dataset.ticketNumber || '';
        if (categorySelect) categorySelect.value = option.dataset.category || '';
        if (siteLocationInput) siteLocationInput.value = option.dataset.location || '';
        if (taskDescriptionInput) taskDescriptionInput.value = option.dataset.task || '';
        if (ticketTotalDisplay) {
            const visits = option.dataset.visits || '0';
            ticketTotalDisplay.value = visits + ' visit' + (visits === '1' ? '' : 's') + ' logged';
        }
    }

    function updateLogTypeUi() {
        const isContinue = logTypeContinue && logTypeContinue.checked;
        if (continueSection) {
            continueSection.style.display = isContinue ? '' : 'none';
        }
        setTicketFieldsLocked(isContinue);
        if (isContinue) {
            applyContinueTicketSelection();
        }
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
            if (dayTotalHint) {
                dayTotalHint.textContent = 'After saving, your total for this day will update automatically.';
            }
            return;
        }

        const start = new Date(dateInput.value + 'T' + startInput.value);
        const end = new Date(dateInput.value + 'T' + endInput.value);

        if (end <= start) {
            timeSpentDisplay.value = '0 min';
            if (dayTotalHint) {
                dayTotalHint.textContent = 'End time must be after start time.';
            }
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
