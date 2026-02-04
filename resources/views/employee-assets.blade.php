@extends('layouts.app')

@section('content')
<style>
    .employee-item {
        transition: all 0.2s ease;
    }
    .employee-item:hover {
        background-color: #f8f9fa !important;
        border-left-color: #C6A87D !important;
        transform: translateX(2px);
    }
    .employee-item.active {
        background-color: #e7f3ff !important;
        border-left-color: #1F2A44 !important;
    }
    #employeeList {
        border: 1px solid #dee2e6;
    }
    #employeeList .list-group-item {
        border: none;
        border-bottom: 1px solid #f0f0f0;
    }
    #employeeList .list-group-item:last-child {
        border-bottom: none;
    }
</style>

<div class="container-fluid">
    <div class="page-header">
        <h2><i class="bi bi-search me-2"></i>Employee Asset Lookup</h2>
       
    </div>

    <div class="form-group position-relative mb-4">
        <label for="employee_name" class="form-label fw-semibold">Search Employee</label>
        <input type="text" id="employee_name" class="form-control form-control-lg" 
               placeholder="Type employee name " 
               autocomplete="off">
        <div id="employeeList" class="list-group position-absolute w-100 mt-1" 
             style="z-index: 1000; max-height: 300px; overflow-y: auto; display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px;"></div>
    </div>

    <div class="table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 style="color: white; margin: 0;"><i class="bi bi-box-seam me-2"></i>Assigned Assets</h5>
            <div class="dropdown" id="downloadDropdown" style="display: none;">
                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Download
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadBtn">
                    <li><a class="dropdown-item" href="#" id="downloadPdf"><i class="bi bi-file-pdf"></i> PDF</a></li>
                    <li><a class="dropdown-item" href="#" id="downloadCsv"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Asset ID</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Serial Number</th>
                            <th>PO Number</th>
                            <th>Location Name</th>
                            <th>Issue Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="assetTableBody">
                        <tr><td colspan="9" class="text-center text-muted py-4">Search an employee to view their assets.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){

    // 🔍 Autocomplete employees - starts from first letter
    let searchTimeout;
    $('#employee_name').on('input', function(){
        let query = $(this).val().trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        if(query.length >= 1){
            // Small delay to avoid too many requests while typing
            searchTimeout = setTimeout(function(){
                $.ajax({
                    url: "{{ route('employees.autocomplete') }}",
                    type: "GET",
                    data: {'query': query},
                    success: function(data){
                        let employeeList = $('#employeeList');
                        employeeList.empty();
                        
                        if (data.length === 0) {
                            employeeList.append('<div class="list-group-item text-muted text-center">No similar employees found</div>');
                        } else {
                            data.forEach(function(employee){
                                let displayName = employee.name || employee.entity_name || 'N/A';
                                let employeeId = employee.employee_id || '';
                                let dept = employee.department_name || '';
                                let designation = employee.designation || '';
                                let extra = [employeeId, dept, designation].filter(Boolean).join(' · ');
                                let highlight = query.length > 0 ? displayName.replace(new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi'), '<strong>$&</strong>') : displayName;
                                
                                employeeList.append(`
                                    <a href="#" class="list-group-item list-group-item-action employee-item" 
                                       data-id="${employee.id}" 
                                       data-name="${displayName.replace(/"/g, '&quot;')}"
                                       style="cursor: pointer; border-left: 3px solid #1F2A44;">
                                        <div class="fw-semibold">${highlight}</div>
                                        ${extra ? '<small class="text-muted">' + extra + '</small>' : ''}
                                    </a>
                                `);
                            });
                        }
                        employeeList.fadeIn(200);
                    },
                    error: function(){
                        $('#employeeList').html('<div class="list-group-item text-danger text-center">Error loading employees</div>').fadeIn();
                    }
                });
            }, 200); // 200ms delay
        } else {
            $('#employeeList').fadeOut(200);
        }
    });

    // Store current employee ID
    let currentEmployeeId = null;

    // 👆 Select employee
    $(document).on('click', '.employee-item', function(e){
        e.preventDefault();
        let employeeId = $(this).data('id');
        let employeeName = $(this).data('name');
        $('#employee_name').val(employeeName);
        $('#employeeList').fadeOut(200);
        currentEmployeeId = employeeId;

        // 🧾 Fetch assets
        $.ajax({
            url: `/employees/${employeeId}/assets`,
            type: "GET",
            success: function(data){
                let tableBody = $('#assetTableBody');
                tableBody.empty();

                if (data.length > 0) {
                    data.forEach(function(asset, index){
                        tableBody.append(`
                            <tr>
                                <td>${index + 1}</td>
                                <td>${asset.asset_id}</td>
                                <td>${asset.category}</td>
                                <td>${asset.brand}</td>
                                <td>${asset.serial_number}</td>
                                <td>${asset.po_number}</td>
                                <td>${asset.location}</td>
                                <td>${asset.issue_date}</td>
                                <td>${asset.status}</td>
                            </tr>
                        `);
                    });
                    // Show download button
                    $('#downloadDropdown').show();
                } else {
                    tableBody.append('<tr><td colspan="9" class="text-center text-muted">No assets assigned to this employee.</td></tr>');
                    $('#downloadDropdown').hide();
                }
            },
            error: function(){
                alert('Error fetching assets. Please try again.');
                $('#downloadDropdown').hide();
            }
        });
    });

    // Download handlers
    $('#downloadPdf').on('click', function(e){
        e.preventDefault();
        if (currentEmployeeId) {
            window.location.href = `/employee-assets/${currentEmployeeId}/export?format=pdf`;
        }
    });

    $('#downloadCsv').on('click', function(e){
        e.preventDefault();
        if (currentEmployeeId) {
            window.location.href = `/employee-assets/${currentEmployeeId}/export?format=csv`;
        }
    });

    // Hide suggestions when clicking outside
    $(document).click(function(e){
        if(!$(e.target).closest('#employee_name, #employeeList').length){
            $('#employeeList').fadeOut(200);
        }
    });
    
    // Keyboard navigation (Arrow keys and Enter)
    let selectedIndex = -1;
    $('#employee_name').on('keydown', function(e){
        let items = $('.employee-item');
        
        if(e.key === 'ArrowDown'){
            e.preventDefault();
            selectedIndex = (selectedIndex < items.length - 1) ? selectedIndex + 1 : 0;
            items.removeClass('active').eq(selectedIndex).addClass('active').focus();
        } else if(e.key === 'ArrowUp'){
            e.preventDefault();
            selectedIndex = (selectedIndex > 0) ? selectedIndex - 1 : items.length - 1;
            items.removeClass('active').eq(selectedIndex).addClass('active').focus();
        } else if(e.key === 'Enter' && selectedIndex >= 0){
            e.preventDefault();
            items.eq(selectedIndex).click();
        } else if(e.key === 'Escape'){
            $('#employeeList').fadeOut(200);
            selectedIndex = -1;
        }
    });
});
</script>
@endsection
