@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h3 class="mb-4 text-center">Employee Asset Lookup</h3>

    <div class="form-group position-relative">
        <label for="employee_name">Search Employee</label>
        <input type="text" id="employee_name" class="form-control" placeholder="Type employee name or ID">
        <div id="employeeList" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
    </div>

    <table class="table table-bordered mt-4">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Asset ID</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Serial Number</th>
                <th>PO Number</th>
                <th>Location</th>
                <th>Issue Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="assetTableBody">
            <tr><td colspan="9" class="text-center text-muted">Search an employee to view their assets.</td></tr>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){

    // 🔍 Autocomplete employees
    $('#employee_name').on('keyup', function(){
        let query = $(this).val();
        if(query.length >= 2){
            $.ajax({
                url: "{{ route('employees.autocomplete') }}",
                type: "GET",
                data: {'query': query},
                success: function(data){
                    $('#employeeList').fadeIn().empty();
                    if (data.length === 0) {
                        $('#employeeList').append('<a href="#" class="list-group-item list-group-item-action disabled">No results found</a>');
                    }
                    data.forEach(function(employee){
                        $('#employeeList').append(`<a href="#" class="list-group-item list-group-item-action employee-item" data-id="${employee.id}" data-name="${employee.name}">${employee.name} (${employee.employee_id})</a>`);
                    });
                }
            });
        } else {
            $('#employeeList').fadeOut();
        }
    });

    // 👆 Select employee
    $(document).on('click', '.employee-item', function(e){
        e.preventDefault();
        let employeeId = $(this).data('id');
        let employeeName = $(this).data('name');
        $('#employee_name').val(employeeName);
        $('#employeeList').fadeOut();

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
                } else {
                    tableBody.append('<tr><td colspan="9" class="text-center text-muted">No assets assigned to this employee.</td></tr>');
                }
            },
            error: function(){
                alert('Error fetching assets. Please try again.');
            }
        });
    });

    // Hide suggestions when clicking outside
    $(document).click(function(e){
        if(!$(e.target).closest('#employee_name, #employeeList').length){
            $('#employeeList').fadeOut();
        }
    });
});
</script>
@endsection
