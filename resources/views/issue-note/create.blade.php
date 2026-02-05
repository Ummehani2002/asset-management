@extends('layouts.app')
@section('content')
<div class="container">
    <h3>System Issue Note</h3>
    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
            @if(session('saved_note_id'))
                <a href="{{ route('issue-note.download-form', session('saved_note_id')) }}" class="btn btn-sm btn-outline-light ms-3">
                    <i class="bi bi-download me-1"></i>Download Form (PDF)
                </a>
            @endif
        </div>
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

    <form action="{{ route('issue-note.store') }}" method="POST" autocomplete="off">
        @csrf

        <div class="row">
            <div class="col-md-3">
                <label>Employee Name</label>
                <select name="employee_id" id="employee_id" class="form-control employee-select" data-placeholder="Type to search...">
                    <option value="">-- Select Employee --</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name ?? $emp->entity_name }}</option>
                    @endforeach
                </select>
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
                <input type="text" name="system_code" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Printer Code</label>
                <input type="text" name="printer_code" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Issued Date</label>
                <input type="date" name="issued_date" class="form-control">
            </div>
        </div>

        <div class="mt-3">
            <label>Installed Software</label>
            <input type="text" name="software_installed" class="form-control">
        </div>

        <div class="mt-3">
            <label>Issued Items</label><br>
            <label><input type="checkbox" name="items[]" value="CD Drive"> CD Drive</label>
            <label><input type="checkbox" name="items[]" value="DVD RW"> DVD RW</label>
            <label><input type="checkbox" name="items[]" value="Keyboard/Mouse"> Keyboard / Mouse</label>
            <label><input type="checkbox" name="items[]" value="Modem/Adapters"> Modem/Adapters</label>
            <label><input type="checkbox" name="items[]" value="FDD"> FDD</label>
            <label><input type="checkbox" name="items[]" value="Printer"> Printer</label>
            <label><input type="checkbox" name="items[]" value="Power Cables"> Power Cables</label>
            <label><input type="checkbox" name="items[]" value="Scanner"> Scanner</label>
            <label><input type="checkbox" name="items[]" value="Driver Softwares"> Driver Softwares</label>
            <label><input type="checkbox" name="items[]" value="Others"> Others</label>
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
<button type="submit" class="btn btn-primary mt-3">Save Issue Note</button>
<button type="button" class="btn btn-secondary mt-3 ms-2" onclick="resetForm(this)">
    <i class="bi bi-x-circle me-2"></i>Cancel
</button>
</form>

{{-- Signature Pad JS --}}
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // Load employee details
    const employeeSelect = document.getElementById('employee_id');
    employeeSelect.addEventListener('change', function() {
        const employeeId = this.value;

        if (employeeId) {
            fetch(`/employee/${employeeId}/details`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('department').value = data.department || data.department_name || 'N/A';
                    document.getElementById('entity').value = data.entity_name || 'N/A';
                    document.getElementById('location').value = data.location || 'N/A';
                })
                .catch(error => {
                    console.error('Error fetching employee details:', error);
                });
        } else {
            document.getElementById('department').value = '';
            document.getElementById('entity').value = '';
            document.getElementById('location').value = '';
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
