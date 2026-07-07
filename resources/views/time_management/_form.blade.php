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
                    <small class="text-muted d-block">Ticket Number</small>
                    <strong class="fs-6 text-primary">{{ old('ticket_number', $ticketNumber) }}</strong>
                    <input type="hidden" name="ticket_number" value="{{ old('ticket_number', $ticketNumber) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <select name="category" class="form-select" required>
                @php $category = old('category', $record?->category ?? \App\Models\TimeManagement::DEFAULT_CATEGORY); @endphp
                <option value="End User Support" {{ $category === 'End User Support' ? 'selected' : '' }}>End User Support</option>
                <option value="Infrastructure" {{ $category === 'Infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                <option value="Network" {{ $category === 'Network' ? 'selected' : '' }}>Network</option>
                <option value="Hardware" {{ $category === 'Hardware' ? 'selected' : '' }}>Hardware</option>
                <option value="Software" {{ $category === 'Software' ? 'selected' : '' }}>Software</option>
                <option value="Other" {{ $category === 'Other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Work Date <span class="text-danger">*</span></label>
            <input type="date" name="job_card_date" class="form-control" required
                   value="{{ old('job_card_date', optional($record?->job_card_date)->format('Y-m-d') ?? date('Y-m-d')) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Site / Location <span class="text-danger">*</span></label>
            <input type="text" name="site_location" class="form-control" required maxlength="100"
                   value="{{ old('site_location', $record?->site_location ?? '') }}"
                   placeholder="e.g. Head Office">
        </div>

        <div class="col-12">
            <label class="form-label">Task Description <span class="text-danger">*</span>
                <small class="text-muted">(max 50 characters)</small>
            </label>
            <input type="text" name="task_description" id="task_description" class="form-control" required
                   maxlength="50"
                   value="{{ old('task_description', $record?->task_description ?? '') }}"
                   placeholder="Short task summary, e.g. Fix laptop WiFi issue">
            <div class="d-flex justify-content-end mt-1">
                <small class="text-muted"><span id="task_desc_count">0</span>/50</small>
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label">Start Time <span class="text-danger">*</span></label>
            <input type="time" name="start_time_hour" id="start_time_hour" class="form-control" required
                   value="{{ old('start_time_hour', $record && $record->start_time ? $record->start_time->format('H:i') : '09:00') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">End Time <span class="text-danger">*</span></label>
            <input type="time" name="end_time_hour" id="end_time_hour" class="form-control" required
                   value="{{ old('end_time_hour', $record && $record->end_time ? $record->end_time->format('H:i') : '17:00') }}">
        </div>

        <div class="col-md-2">
            <label class="form-label">Time Spent</label>
            <input type="text" id="time_spent_display" class="form-control bg-white" readonly value="0.00 hrs">
        </div>

        <div class="col-md-2">
            <label class="form-label">Standard</label>
            <input type="text" class="form-control bg-white" readonly value="8 hrs">
        </div>

        <div class="col-md-2">
            <label class="form-label">Overtime</label>
            <input type="text" id="overtime_hint" class="form-control bg-white" readonly value="On save">
        </div>

        <div class="col-md-8">
            <label class="form-label">Action Taken / Resolution</label>
            <input type="text" name="action_taken" class="form-control" maxlength="150"
                   value="{{ old('action_taken', $record?->action_taken ?? '') }}"
                   placeholder="What was done to resolve the task?">
        </div>

        <div class="col-md-4">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            @php $status = old('status', $record?->status ?? 'pending'); if ($status === 'in_progress') $status = 'pending'; @endphp
            <select name="status" class="form-select" required>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.querySelector('input[name="job_card_date"]');
    const startInput = document.getElementById('start_time_hour');
    const endInput = document.getElementById('end_time_hour');
    const timeSpentDisplay = document.getElementById('time_spent_display');
    const taskDescInput = document.getElementById('task_description');
    const taskDescCount = document.getElementById('task_desc_count');

    function updateTaskCount() {
        taskDescCount.textContent = (taskDescInput.value || '').length;
    }

    function updateTimeSpent() {
        if (!dateInput.value || !startInput.value || !endInput.value) {
            timeSpentDisplay.value = '0.00 hrs';
            return;
        }

        const start = new Date(dateInput.value + 'T' + startInput.value);
        const end = new Date(dateInput.value + 'T' + endInput.value);

        if (end <= start) {
            timeSpentDisplay.value = '0.00 hrs';
            return;
        }

        const hours = ((end - start) / (1000 * 60 * 60)).toFixed(2);
        timeSpentDisplay.value = hours + ' hrs';
    }

    taskDescInput.addEventListener('input', updateTaskCount);
    dateInput.addEventListener('change', updateTimeSpent);
    startInput.addEventListener('change', updateTimeSpent);
    endInput.addEventListener('change', updateTimeSpent);

    updateTaskCount();
    updateTimeSpent();
});
</script>
