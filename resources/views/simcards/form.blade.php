@extends('layouts.app')

@section('content')
<div class="container">
    <h2>SIM Transaction</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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

    <form action="{{ route('simcards.store') }}" method="POST" id="simcardForm">
        @csrf

        <div class="mb-3">
            <label for="transaction_type" class="form-label">Transaction Type</label>
            <select name="transaction_type" id="transaction_type" class="form-control">
                <option value="assign">Assign</option>
                <option value="return">Return</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="simcard_number" class="form-label">SIM Number</label>
            <input type="text" name="simcard_number" id="simcard_number" class="form-control" required>
        </div>

        <div class="mb-3" id="projectDiv">
            <label for="project_id" class="form-label">Project</label>
            <select name="project_id" id="project_id" class="form-control">
                <option value="">Select Project</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}">
                        {{ $project->project_id }} - {{ $project->project_name }} ({{ $project->entity }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3" id="issueDateDiv">
            <label for="issue_date" class="form-label">Issue Date</label>
            <input type="date" name="issue_date" id="issue_date" class="form-control">
        </div>

        <div class="mb-3" id="returnDateDiv" style="display:none;">
            <label for="return_date" class="form-label">Return Date</label>
            <input type="date" name="return_date" id="return_date" class="form-control">
        </div>

        <div class="mb-3">
            <label for="mrc" class="form-label">MRC</label>
            <input type="number" name="mrc" id="mrc" class="form-control">
        </div>

        <div class="mb-3">
            <label for="pm_dc" class="form-label">PM/DC</label>
            <input type="text" name="pm_dc" id="pm_dc" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
        <a href="{{ route('simcards.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
    const transactionType = document.getElementById('transaction_type');
    const issueDiv = document.getElementById('issueDateDiv');
    const returnDiv = document.getElementById('returnDateDiv');
    const projectDiv = document.getElementById('projectDiv');
    const form = document.getElementById('simcardForm');
    const submitBtn = document.getElementById('submitBtn');

    // Handle transaction type change
    transactionType.addEventListener('change', function() {
        if(this.value === 'assign') {
            issueDiv.style.display = 'block';
            returnDiv.style.display = 'none';
            projectDiv.style.display = 'block';
            document.getElementById('issue_date').required = true;
            document.getElementById('project_id').required = true;
            document.getElementById('return_date').required = false;
        } else {
            issueDiv.style.display = 'none';
            returnDiv.style.display = 'block';
            projectDiv.style.display = 'none';
            document.getElementById('issue_date').required = false;
            document.getElementById('project_id').required = false;
            document.getElementById('return_date').required = true;
        }
    });

    // Form submission handler
    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            // Validate required fields based on transaction type
            const txType = transactionType.value;
            
            if (txType === 'assign') {
                const projectId = document.getElementById('project_id').value;
                const issueDate = document.getElementById('issue_date').value;
                
                if (!projectId || !issueDate) {
                    e.preventDefault();
                    alert('Please fill in all required fields for assignment (Project and Issue Date)');
                    return false;
                }
            } else if (txType === 'return') {
                const returnDate = document.getElementById('return_date').value;
                
                if (!returnDate) {
                    e.preventDefault();
                    alert('Please enter a return date');
                    return false;
                }
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
            
            return true;
        });
    }

    // Initialize on page load
    if (transactionType.value) {
        transactionType.dispatchEvent(new Event('change'));
    }
</script>
@endsection
