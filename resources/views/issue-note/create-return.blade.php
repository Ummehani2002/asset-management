@extends('layouts.app')
@section('content')
<div class="container">
    <h3>System Return Note</h3>
    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Error Message --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('issue-note.store-return') }}" method="POST">
        @csrf

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Select Issue Note <span class="text-danger">*</span></label>
                <select name="issue_note_id" id="issue_note_id" class="form-control" required>
                    <option value="">-- Select Issue Note --</option>
                    @foreach ($issueNotes as $note)
                        <option value="{{ $note->id }}">
                            {{ $note->employee->name ?? $note->employee->entity_name ?? 'N/A' }} - 
                            {{ $note->system_code ?? 'N/A' }} - 
                            {{ $note->issued_date ? $note->issued_date->format('Y-m-d') : 'N/A' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label>Return Date <span class="text-danger">*</span></label>
                <input type="date" name="return_date" id="return_date" class="form-control" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <label>Employee Name</label>
                <input type="text" id="employee_name" class="form-control" readonly>
            </div>

            <div class="col-md-3">
                <label>Department</label>
                <input type="text" id="department" name="department" class="form-control" readonly>
            </div>

            <div class="col-md-3">
                <label>Entity</label>
                <input type="text" id="entity" name="entity" class="form-control" readonly>
            </div>

            <div class="col-md-3">
                <label>Location</label>
                <input type="text" id="location" name="location" class="form-control" readonly>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-4">
                <label>System Code</label>
                <input type="text" id="system_code" name="system_code" class="form-control" readonly>
            </div>

            <div class="col-md-4">
                <label>Printer Code</label>
                <input type="text" id="printer_code" name="printer_code" class="form-control" readonly>
            </div>

            <div class="col-md-4">
                <label>Issued Date</label>
                <input type="text" id="issued_date" class="form-control" readonly>
            </div>
        </div>

        <div class="mt-3">
            <label>Installed Software</label>
            <input type="text" id="software_installed" name="software_installed" class="form-control" readonly>
        </div>

        <div class="mt-3">
            <label>Issued Items</label><br>
            <div id="items-container">
                <!-- Items will be populated here -->
            </div>
        </div>

        {{-- SIGNATURE PAD --}}
        <div class="row mt-4">
            {{-- USER SIGNATURE --}}
            <div class="col-md-6">
                <label><strong>User Signature</strong></label>
                <div id="user-signature-container" style="border:1px solid #ccccccd4; width:100%; height:200px; position: relative; background: white;">
                    <canvas id="user-pad" style="width: 100%; height: 100%; display: block; touch-action: none;"></canvas>
                </div>
                <button type="button" id="user-clear" class="btn btn-secondary mt-2">Clear</button>
                <input type="hidden" name="user_signature" id="user_signature">
            </div>

            {{-- MANAGER SIGNATURE --}}
            <div class="col-md-6">
                <label><strong>IT Manager Signature</strong></label>
                <div id="manager-signature-container" style="border:1px solid #ccc; width:100%; height:200px; position: relative; background: white;">
                    <canvas id="manager-pad" style="width: 100%; height: 100%; display: block; touch-action: none;"></canvas>
                </div>
                <button type="button" id="manager-clear" class="btn btn-secondary mt-2">Clear</button>
                <input type="hidden" name="manager_signature" id="manager_signature">
            </div>
        </div>
<button class="btn btn-primary mt-3">Save Return Note</button> </form>

{{-- Signature Pad JS --}}
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // Load issue note details when selected
    const issueNoteSelect = document.getElementById('issue_note_id');
    issueNoteSelect.addEventListener('change', function() {
        const issueNoteId = this.value;

        if (issueNoteId) {
            fetch(`/issue-note/${issueNoteId}/details`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('employee_name').value = data.employee_name || 'N/A';
                    document.getElementById('department').value = data.department || 'N/A';
                    document.getElementById('entity').value = data.entity || 'N/A';
                    document.getElementById('location').value = data.location || 'N/A';
                    document.getElementById('system_code').value = data.system_code || '';
                    document.getElementById('printer_code').value = data.printer_code || '';
                    document.getElementById('software_installed').value = data.software_installed || '';
                    document.getElementById('issued_date').value = data.issued_date || '';

                    // Populate items checkboxes
                    const itemsContainer = document.getElementById('items-container');
                    const allItems = ['CD Drive', 'DVD RW', 'Keyboard/Mouse', 'Modem/Adapters', 'FDD', 'Printer', 'Power Cables', 'Scanner', 'Driver Softwares', 'Others'];
                    let html = '';
                    allItems.forEach(function(item) {
                        const checked = data.items && data.items.includes(item) ? 'checked' : '';
                        html += `<label class="me-3"><input type="checkbox" value="${item}" ${checked} disabled> ${item}</label>`;
                    });
                    itemsContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error fetching issue note details:', error);
                });
        } else {
            // Clear fields
            document.getElementById('employee_name').value = '';
            document.getElementById('department').value = '';
            document.getElementById('entity').value = '';
            document.getElementById('location').value = '';
            document.getElementById('system_code').value = '';
            document.getElementById('printer_code').value = '';
            document.getElementById('software_installed').value = '';
            document.getElementById('issued_date').value = '';
            document.getElementById('items-container').innerHTML = '';
        }
    });

    // Function to resize canvas for signature pad
    function resizeCanvas(canvas, container) {
        const rect = container.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        
        // Set canvas size to match container (accounting for DPR)
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        
        // Scale context for high DPI displays
        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        
        // Set CSS size to match container
        canvas.style.width = rect.width + 'px';
        canvas.style.height = rect.height + 'px';
    }

    // Initialize signature pads
    const userCanvas = document.getElementById('user-pad');
    const userContainer = document.getElementById('user-signature-container');
    const managerCanvas = document.getElementById('manager-pad');
    const managerContainer = document.getElementById('manager-signature-container');
    
    // Resize canvases first
    resizeCanvas(userCanvas, userContainer);
    resizeCanvas(managerCanvas, managerContainer);
    
    // Initialize SignaturePad instances
    const userPad = new SignaturePad(userCanvas, {
        backgroundColor: 'rgb(255, 255, 255)',
        penColor: 'rgb(0, 0, 0)',
        minWidth: 1,
        maxWidth: 3,
    });
    
    const managerPad = new SignaturePad(managerCanvas, {
        backgroundColor: 'rgb(255, 255, 255)',
        penColor: 'rgb(0, 0, 0)',
        minWidth: 1,
        maxWidth: 3,
    });

    // Clear buttons
    document.getElementById('user-clear').addEventListener('click', () => {
        userPad.clear();
    });

    document.getElementById('manager-clear').addEventListener('click', () => {
        managerPad.clear();
    });

    // Resize signature pads when window is resized
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            // Save current signatures
            const userData = userPad.toData();
            const managerData = managerPad.toData();
            
            // Resize canvases
            resizeCanvas(userCanvas, userContainer);
            resizeCanvas(managerCanvas, managerContainer);
            
            // Clear and restore signatures
            userPad.clear();
            managerPad.clear();
            if (userData && userData.length > 0) {
                userPad.fromData(userData);
            }
            if (managerData && managerData.length > 0) {
                managerPad.fromData(managerData);
            }
        }, 250);
    });

    // Attach signatures on submit
    document.querySelector("form").addEventListener("submit", function(e) {
        if (!userPad.isEmpty()) {
            document.getElementById('user_signature').value = userPad.toDataURL("image/png");
        }
        if (!managerPad.isEmpty()) {
            document.getElementById('manager_signature').value = managerPad.toDataURL("image/png");
        }
    });

});
</script>
@endsection

