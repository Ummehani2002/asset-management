@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Add New Job Card</h2>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('time.store') }}" method="POST" id="timeManagementForm">
        @csrf

        <div class="mb-3">
            <label for="employee_id" class="form-label">Employee <span class="text-danger">*</span></label>
            <select name="employee_id" id="employee_id" class="form-control" required>
                <option value="">-- Select Employee --</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->email }})</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3" id="project_section" style="display:none;">
            <label for="project_id" class="form-label">Project</label>
            <select name="project_id" id="project_id" class="form-control">
                <option value="">-- Select Project --</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" data-project-name="{{ $project->project_name }}">
                        {{ $project->project_id }} - {{ $project->project_name }} ({{ $project->entity }})
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Or enter project name manually below</small>
        </div>

        <div class="mb-3" id="project_name_section" style="display:none;">
            <label for="project_name" class="form-label">Project Name <span class="text-danger">*</span></label>
            <input type="text" name="project_name" id="project_name" class="form-control" 
                   placeholder="Enter project name">
        </div>

        <div class="mb-3">
            <label for="job_card_date" class="form-label">Job Card Date <span class="text-danger">*</span></label>
            <input type="date" name="job_card_date" id="job_card_date" class="form-control" 
                   value="{{ date('Y-m-d') }}" required>
        </div>

        <div class="mb-3">
            <label for="standard_man_hours" class="form-label">Allocated Time (in Hours) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="standard_man_hours" id="standard_man_hours" 
                   class="form-control" placeholder="e.g., 0.5 (30 min), 1.0 (1 hour), 2.0 (2 hours)" required min="0.1">
            <small class="text-muted">
                <strong>Enter the allocated time in hours:</strong><br>
                • 0.5 = 30 minutes<br>
                • 1.0 = 1 hour<br>
                • 2.0 = 2 hours<br>
                <strong style="color: #dc3545;">An email will be sent to the employee when task is assigned. If the task is not completed within this allocated time, reminder emails will be sent every hour until completed.</strong>
            </small>
        </div>

        <button type="submit" class="btn btn-primary" id="submitBtn">
            <i class="bi bi-check-circle me-2"></i>Assign Task
        </button>
        <a href="{{ route('time.index') }}" class="btn btn-secondary">
            <i class="bi bi-x-circle me-2"></i>Cancel
        </a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeSelect = document.getElementById('employee_id');
    const projectSection = document.getElementById('project_section');
    const projectNameSection = document.getElementById('project_name_section');
    const projectSelect = document.getElementById('project_id');
    const projectNameInput = document.getElementById('project_name');
    const form = document.getElementById('timeManagementForm');
    const submitBtn = document.getElementById('submitBtn');

    // Show project selection when employee is selected
    employeeSelect.addEventListener('change', function() {
        if (this.value) {
            projectSection.style.display = 'block';
            projectNameSection.style.display = 'block';
        } else {
            projectSection.style.display = 'none';
            projectNameSection.style.display = 'none';
        }
    });

    // Auto-fill project name when project is selected
    projectSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.dataset.projectName) {
            projectNameInput.value = selectedOption.dataset.projectName;
        }
    });

    // Form validation
    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            const projectId = projectSelect.value;
            const projectName = projectNameInput.value.trim();
            
            if (!projectId && !projectName) {
                e.preventDefault();
                alert('Please select a project or enter a project name');
                return false;
            }

            // If project is selected, use its name
            if (projectId && !projectName) {
                const selectedOption = projectSelect.options[projectSelect.selectedIndex];
                if (selectedOption && selectedOption.dataset.projectName) {
                    projectNameInput.value = selectedOption.dataset.projectName;
                }
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Assigning...';
            return true;
        });
    }
});
</script>
@endsection
