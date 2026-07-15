@php
    $isTimeAdmin = $isAdmin ?? auth()->user()?->isTimeManagementAdmin() ?? false;
    $todayTotals = $todayTotals ?? ['total_hours' => 0, 'job_count' => 0];
    $isEdit = ! empty($record);
    $linkedTicket = $record?->workTicket;
    $runningLog = $runningLog ?? null;
    $openTickets = $openTickets ?? collect();
    $continueTicket = $continueTicket ?? null;
    $defaultLogType = old('log_type', $continueTicket ? 'continue' : 'new');
    $workDate = optional($record?->job_card_date)->format('Y-m-d') ?? date('Y-m-d');
    $defaultStart = $record && $record->start_time ? $record->start_time->format('H:i') : now()->format('H:i');
    $defaultEnd = $record && $record->end_time ? $record->end_time->format('H:i') : '';
@endphp

@if(! $isEdit && $runningLog)
    <div class="alert alert-warning">
        You already have a running work log
        <strong>{{ $runningLog->ticket_number }}</strong>
        (started {{ $runningLog->start_time?->format('H:i') }}).
        Stop it before starting a new one.
        <form action="{{ route('time.stop', $runningLog->id) }}" method="POST" class="d-inline ms-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-danger">Stop Now</button>
        </form>
        <a href="{{ route('time.index') }}" class="btn btn-sm btn-outline-secondary ms-1">View logs</a>
    </div>
@endif

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
                <div>
                    <small class="text-muted d-block">Logged today (completed)</small>
                    <strong class="fs-6 text-success">{{ \App\Models\TimeManagement::formatDuration($todayTotals['total_hours']) }}</strong>
                    <small class="text-muted d-block">{{ $todayTotals['job_count'] }} job(s) so far</small>
                </div>
            </div>
        </div>
    </div>

    @if($isEdit && $linkedTicket)
    <div class="alert alert-light border mb-3">
        <div class="d-flex flex-wrap gap-4">
            <div><small class="text-muted d-block">Ticket</small><strong>{{ $linkedTicket->ticket_number }}</strong></div>
            <div><small class="text-muted d-block">Location</small><strong>{{ $linkedTicket->site_location }}</strong></div>
            <div><small class="text-muted d-block">Total on ticket</small><strong>{{ \App\Models\TimeManagement::formatDuration($linkedTicket->totalDurationHours()) }}</strong></div>
        </div>
        <a href="{{ route('time.ticket.show', $linkedTicket->id) }}" class="small">View ticket</a>
    </div>
    @endif

    @unless($isEdit)
        @if($openTickets->isNotEmpty())
        <div class="border rounded p-3 mb-4">
            <label class="form-label fw-semibold text-uppercase small d-block">Log Type</label>
            <div class="d-flex gap-4">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="log_type" id="log_type_new"
                           value="new" {{ $defaultLogType === 'new' ? 'checked' : '' }}>
                    <label class="form-check-label" for="log_type_new">New Ticket</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="log_type" id="log_type_continue"
                           value="continue" {{ $defaultLogType === 'continue' ? 'checked' : '' }}>
                    <label class="form-check-label" for="log_type_continue">Continue Ticket / Add Visit</label>
                </div>
            </div>

            <div id="continue_ticket_section" class="mt-3" style="{{ $defaultLogType === 'continue' ? '' : 'display:none;' }}">
                <label class="form-label fw-semibold text-uppercase small">Open Ticket</label>
                <select name="work_ticket_id" id="work_ticket_id" class="form-select">
                    <option value="">Select an open ticket</option>
                    @foreach($openTickets as $ticket)
                        <option value="{{ $ticket->id }}"
                                data-ticket="{{ $ticket->ticket_number }}"
                                data-category="{{ $ticket->category }}"
                                data-location="{{ $ticket->site_location }}"
                                data-description="{{ $ticket->task_description }}"
                                {{ (string) old('work_ticket_id', $continueTicket?->id) === (string) $ticket->id ? 'selected' : '' }}>
                            {{ $ticket->ticket_number }} — {{ $ticket->site_location }} ({{ $ticket->visits_count }} visit{{ $ticket->visits_count === 1 ? '' : 's' }})
                        </option>
                    @endforeach
                </select>
                <div id="continue_ticket_summary" class="small text-muted mt-2"></div>
            </div>
        </div>
        @else
            <input type="hidden" name="log_type" value="new">
        @endif
    @endunless

    <div class="row g-4">
        <div class="col-md-6 ticket-input-field">
            <label class="form-label fw-semibold text-uppercase small">Ticket ID <span class="text-danger">*</span></label>
            <input type="text" name="ticket_number" id="ticket_number" class="form-control"
                   maxlength="50"
                   value="{{ old('ticket_number', $record?->ticket_number ?? '') }}"
                   placeholder="e.g. INC-0001"
                   {{ $isEdit && $record?->work_ticket_id ? 'readonly' : '' }}
                   required>
        </div>

        <div class="col-md-6 ticket-input-field">
            <label class="form-label fw-semibold text-uppercase small">Category <span class="text-danger">*</span></label>
            @php $selectedCategory = old('category', $record?->category ?? \App\Models\TimeManagement::DEFAULT_CATEGORY); @endphp
            <select name="category" id="category" class="form-select"
                    {{ $isEdit && $record?->work_ticket_id ? 'disabled' : '' }} required>
                @foreach(['End User Support', 'Infrastructure', 'Network', 'Hardware', 'Software', 'Other'] as $categoryOption)
                    <option value="{{ $categoryOption }}" {{ $selectedCategory === $categoryOption ? 'selected' : '' }}>
                        {{ $categoryOption }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold text-uppercase small">Work Date</label>
            @if($isEdit)
                <input type="date" name="job_card_date" class="form-control" required value="{{ old('job_card_date', $workDate) }}">
            @else
                <input type="text" class="form-control bg-light" readonly value="{{ date('Y-m-d') }}">
            @endif
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold text-uppercase small">Start Time</label>
            @if($isEdit)
                <input type="time" name="start_time_hour" id="start_time_hour" class="form-control" required
                       value="{{ old('start_time_hour', $defaultStart) }}">
            @else
                <input type="text" class="form-control bg-light" readonly value="{{ now()->format('H:i') }}">
            @endif
        </div>

        <div class="col-12 ticket-input-field">
            <label class="form-label fw-semibold text-uppercase small">Site / Location <span class="text-danger">*</span></label>
            <input type="text" name="site_location" id="site_location" class="form-control" maxlength="100"
                   value="{{ old('site_location', $record?->site_location ?? '') }}"
                   placeholder="e.g. Head Office"
                   {{ $isEdit && $record?->work_ticket_id ? 'readonly' : '' }}
                   required>
        </div>

        <div class="col-12 ticket-input-field">
            <label class="form-label fw-semibold text-uppercase small">Description <span class="text-danger">*</span></label>
            <textarea name="task_description" id="task_description" class="form-control"
                      rows="3" maxlength="50"
                      placeholder="Short summary of the work"
                      {{ $isEdit && $record?->work_ticket_id ? 'readonly' : '' }}
                      required>{{ old('task_description', $record?->task_description ?? '') }}</textarea>
            <div class="d-flex justify-content-end mt-1">
                <small class="text-muted"><span id="task_desc_count">0</span>/50</small>
            </div>
        </div>

        @if($isEdit)
        <div class="col-md-4">
            <label class="form-label fw-semibold text-uppercase small">End Time</label>
            <input type="time" name="end_time_hour" id="end_time_hour" class="form-control"
                   value="{{ old('end_time_hour', $defaultEnd) }}">
            <small class="text-muted">Leave empty if still running</small>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold text-uppercase small">Ticket Status <span class="text-danger">*</span></label>
            @php $status = old('status', $record?->ticketStatus() ?? 'pending'); if ($status === 'in_progress') $status = 'pending'; @endphp
            <select name="status" class="form-select" required>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending / Running</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
        @endif

    </div>

    <div class="d-flex gap-2 mt-4 pt-3 border-top">
        @if($isEdit)
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-2"></i>Update Work Log
            </button>
        @else
            <button type="submit" class="btn btn-primary" {{ $runningLog ? 'disabled' : '' }}>
                <i class="bi bi-play-circle me-2"></i>Start Work
            </button>
        @endif
        <a href="{{ route('time.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const taskDescInput = document.getElementById('task_description');
    const taskDescCount = document.getElementById('task_desc_count');
    const newRadio = document.getElementById('log_type_new');
    const continueRadio = document.getElementById('log_type_continue');
    const continueSection = document.getElementById('continue_ticket_section');
    const ticketSelect = document.getElementById('work_ticket_id');
    const ticketSummary = document.getElementById('continue_ticket_summary');
    const ticketFields = document.querySelectorAll('.ticket-input-field');
    const ticketInput = document.getElementById('ticket_number');
    const categoryInput = document.getElementById('category');
    const locationInput = document.getElementById('site_location');

    function updateTicketMode() {
        const continuing = continueRadio && continueRadio.checked;
        if (continueSection) continueSection.style.display = continuing ? '' : 'none';
        ticketFields.forEach(function (field) {
            field.style.display = continuing ? 'none' : '';
        });
        [ticketInput, categoryInput, locationInput, taskDescInput].forEach(function (input) {
            if (input) input.required = !continuing;
        });
        if (ticketSelect) ticketSelect.required = continuing;
        updateTicketSummary();
    }

    function updateTicketSummary() {
        if (!ticketSelect || !ticketSummary) return;
        const option = ticketSelect.selectedOptions[0];
        if (!option || !option.value) {
            ticketSummary.textContent = '';
            return;
        }
        ticketSummary.textContent = option.dataset.ticket + ' · ' +
            option.dataset.category + ' · ' + option.dataset.location + ' · ' +
            option.dataset.description;
    }

    if (newRadio) newRadio.addEventListener('change', updateTicketMode);
    if (continueRadio) continueRadio.addEventListener('change', updateTicketMode);
    if (ticketSelect) ticketSelect.addEventListener('change', updateTicketSummary);
    updateTicketMode();

    if (taskDescInput && taskDescCount) {
        const updateTaskCount = function () {
            taskDescCount.textContent = (taskDescInput.value || '').length;
        };
        taskDescInput.addEventListener('input', updateTaskCount);
        updateTaskCount();
    }
});
</script>
