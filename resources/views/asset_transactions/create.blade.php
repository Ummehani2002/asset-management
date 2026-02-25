@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ isset($transaction) ? 'Edit' : '' }} Asset Transaction</h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong>
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php $isEdit = isset($transaction); @endphp

    <form method="POST" 
          action="{{ $isEdit ? route('asset-transactions.update', $transaction->id) : route('asset-transactions.store') }}" 
          enctype="multipart/form-data" 
          id="transactionForm"
          autocomplete="off"
          novalidate
          data-preselect-asset="{{ $isEdit ? (old('asset_id', $transaction->asset_id ?? $transaction->asset->id ?? '')) : '' }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- 1. Transaction Type (first) --}}
        <div class="mb-3" id="transaction_type_section">
            <label for="transaction_type">Transaction Type <span class="text-danger">*</span></label>
            <select name="transaction_type" id="transaction_type" class="form-control" required>
                <option value="">-- Select Transaction Type --</option>
                <option value="assign" @if(old('transaction_type', $transaction->transaction_type ?? '') == 'assign') selected @endif>Assign</option>
                <option value="return" @if(old('transaction_type', $transaction->transaction_type ?? '') == 'return') selected @endif>Return</option>
            </select>
           
        </div>

        {{-- 2. Asset Category (after transaction type) --}}
        <div class="mb-3" id="category_section" style="{{ $isEdit ? 'display:block;' : 'display:none;' }}">
            <label for="asset_category_id">Asset Category <span class="text-danger">*</span></label>
            <select name="asset_category_id" id="asset_category_id" class="form-control" required>
                <option value="">-- Select Category --</option>
                @php $categoriesUseProjectName = $categoriesUseProjectName ?? []; @endphp
                @foreach($categories as $cat)
                    @php $assignmentType = in_array(strtolower($cat->category_name ?? ''), $categoriesUseProjectName) ? 'project' : 'employee'; @endphp
                    <option value="{{ $cat->id }}" data-assignment-type="{{ $assignmentType }}"
                        @if(old('asset_category_id', $transaction->asset->asset_category_id ?? '') == $cat->id) selected @endif>
                        {{ $cat->category_name }}
                    </option>
                @endforeach
            </select>
         
        </div>

        {{-- 3. Asset (Serial Number) – type to search, dropdown of similar --}}
        <div class="mb-3" id="asset_selection_section" style="{{ $isEdit ? 'display:block;' : 'display:none;' }}">
            <label for="asset_search">Asset (Serial Number) <span class="text-danger">*</span></label>
            <div class="position-relative" id="asset_search_wrap">
                <input type="text" id="asset_search" class="form-control" placeholder="Type serial number or asset ID..."
                       value="{{ $isEdit && $transaction->asset ? $transaction->asset->serial_number . ' (' . ($transaction->asset->asset_id ?? '') . ')' : '' }}"
                       autocomplete="off">
                <input type="hidden" name="asset_id" id="asset_id" value="{{ old('asset_id', $transaction->asset_id ?? $transaction->asset->id ?? '') }}" required>
                <div id="asset_dropdown" class="list-group position-absolute start-0 end-0 mt-1 shadow-sm border rounded"
                     style="z-index: 9999; display: none; max-height: 220px; overflow-y: auto; background: #fff;"></div>
            </div>
            <small class="text-muted" id="asset_status_info">Type initial letters of serial number or asset ID to see matching assets.</small>
        </div>

        {{-- Employee Selection (for Laptop - Assign) - Type name or ID to search --}}
        @php
            $editEmployeeId = old('employee_id', $transaction->employee_id ?? '');
            $editEmployee = $editEmployeeId ? \App\Models\Employee::find($editEmployeeId) : null;
            $editEmployeeDisplay = $editEmployee ? ($editEmployee->name . ' (' . $editEmployee->employee_id . ')') : old('employee_display', '');
        @endphp
        <div class="mb-3" id="employee_section" style="{{ ($isEdit && ($transaction->transaction_type ?? '') == 'assign') ? 'display:block;' : 'display:none;' }}">
            <label for="employee_search">Employee Name or ID <span class="text-danger" id="employee_required">*</span></label>
            <div class="position-relative" id="employee_search_wrap">
                <input type="text" id="employee_search" class="form-control" placeholder="Type name or employee ID..."
                       value="{{ $editEmployeeDisplay }}"
                       autocomplete="off">
                <input type="hidden" name="employee_id" id="employee_id" value="{{ $editEmployeeId }}">
                <div id="employee_dropdown" class="list-group position-absolute start-0 end-0 mt-1 shadow-sm border rounded" 
                     style="z-index: 9999; display: none; max-height: 220px; overflow-y: auto; background: #fff;"></div>
            </div>
            <small class="text-muted" id="employee_auto_fill_info">Type initial letters of name or ID to search</small>
        </div>
        
        {{-- Hidden employee_id field for return transactions (always included in form) --}}
        <input type="hidden" name="employee_id_return" id="employee_id_return" value="">

        {{-- Employee Display (for Return - Read-only) --}}
        <div class="mb-3" id="employee_display_section" style="display:none;">
            <label>Assigned Employee Details</label>
            <div class="card bg-light p-3">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Employee Name:</strong><br>
                        <span id="display_employee_name">-</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Employee ID:</strong><br>
                        <span id="display_employee_id">-</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Entity:</strong><br>
                        <span id="display_employee_entity">-</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Project Name (for Printer) --}}
        <div class="mb-3" id="project_section" style="display:none;">
            <label for="project_name">Project Name <span class="text-danger" id="project_required">*</span></label>
            <input type="text" name="project_name" id="project_name" class="form-control"
                   value="{{ old('project_name', $transaction->project_name ?? '') }}"
                   placeholder="Enter project name">
            <small class="text-muted" id="project_auto_fill_info"></small>
            <select id="project_select" class="form-control mt-2" onchange="document.getElementById('project_name').value = this.value">
                <option value="">Or select existing project</option>
                @foreach($projects as $proj)
                    <option value="{{ $proj->project_name }}">{{ $proj->project_name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Entity & Location (for Assign - link location to entity) --}}
        @php
            $editLocationId = old('location_id', $transaction->location_id ?? '');
            $editLocation = $editLocationId ? \App\Models\Location::find($editLocationId) : null;
            $editEntityName = $editLocation ? $editLocation->location_entity : '';
            // If no location, try to get entity from employee
            if (!$editEntityName && isset($transaction) && $transaction->employee) {
                $editEntityName = $transaction->employee->entity_name ?? '';
            }
        @endphp
        <input type="hidden" name="location_id" id="location_id" value="{{ $editLocationId }}">
        <input type="hidden" id="edit_entity_name" value="{{ $editEntityName }}">
        <input type="hidden" id="edit_location_id" value="{{ $editLocationId }}">
        <div class="mb-3" id="entity_location_section" style="{{ ($isEdit && ($transaction->transaction_type ?? '') == 'assign') ? 'display:block;' : 'display:none;' }}">
            <label class="form-label">Entity & Location <span class="text-muted">(for this assignment)</span></label>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label for="assign_entity" class="form-label small">Entity</label>
                    <select id="assign_entity" class="form-control">
                        <option value="">-- Select Entity --</option>
                        @foreach($entities ?? [] as $ent)
                            <option value="{{ $ent->name }}" @if($editEntityName && strtolower($ent->name) == strtolower($editEntityName)) selected @endif>{{ ucwords($ent->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label for="assign_location" class="form-label small">Location</label>
                    <select id="assign_location" class="form-control">
                        <option value="">-- Select Entity first --</option>
                    </select>
                </div>
            </div>
            <small class="text-muted">Select entity then location. Filled automatically when asset is selected.</small>
        </div>
        <div class="mb-3" id="transaction_type_info_wrapper" style="display:none;">
            <small class="text-muted" id="transaction_type_info"></small>
        </div>

        {{-- Transaction Specific Fields --}}
        {{-- Assign Fields --}}
        <div class="mb-3" id="assign_fields" style="{{ ($isEdit && ($transaction->transaction_type ?? '') == 'assign') ? 'display:block;' : 'display:none;' }}">
            <label for="issue_date">Assigned Date <span class="text-danger">*</span></label>
            <input type="date" name="issue_date" id="issue_date" class="form-control" required
                   value="{{ old('issue_date', $transaction->issue_date ?? date('Y-m-d')) }}">
            
            <div class="mb-3 mt-3">
                <label for="assign_image" class="form-label">
                    <i class="bi bi-camera me-2"></i>Upload Asset Image
                </label>
                <input type="file" name="assign_image" id="assign_image" class="form-control" accept="image/*">
                <small class="text-muted">Upload an image of the asset during assignment (Max: 5MB, Formats: JPG, PNG, GIF)</small>
                @if(isset($transaction) && $transaction->assign_image)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $transaction->assign_image) }}" alt="Assign Image" 
                             class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                        <p class="text-muted small mt-1">Current Image</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Return Fields --}}
        <div class="mb-3" id="return_fields" style="{{ ($isEdit && ($transaction->transaction_type ?? '') == 'return') ? 'display:block;' : 'display:none;' }}">
            <label for="return_date">Return Date <span class="text-danger">*</span></label>
            <input type="date" name="return_date" id="return_date" class="form-control" required
                   value="{{ old('return_date', $transaction->return_date ?? date('Y-m-d')) }}">
            
            <div class="mb-3 mt-3">
                <label for="return_image" class="form-label">
                    <i class="bi bi-camera me-2"></i>Upload Asset Image (Return)
                </label>
                <input type="file" name="return_image" id="return_image" class="form-control" accept="image/*">
                <small class="text-muted">Upload an image of the asset during return (Max: 5MB, Formats: JPG, PNG, GIF)</small>
                @if(isset($transaction) && $transaction->return_image)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $transaction->return_image) }}" alt="Return Image" 
                             class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                        <p class="text-muted small mt-1">Current Image</p>
                    </div>
                @endif
            </div>
        </div>


        <button type="submit" class="btn btn-primary" id="submitBtn">
            <i class="bi bi-check-circle me-2"></i>{{ $isEdit ? 'Update' : 'Save' }} Transaction
        </button>
        <button type="button" class="btn btn-secondary ms-2" onclick="resetForm(this)">
            <i class="bi bi-x-circle me-2"></i>Cancel
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryDropdown = document.getElementById('asset_category_id');
    const assetIdInput = document.getElementById('asset_id');
    const assetSearchInput = document.getElementById('asset_search');
    const assetDropdownEl = document.getElementById('asset_dropdown');
    const assetSearchWrap = document.getElementById('asset_search_wrap');
    const employeeSection = document.getElementById('employee_section');
    const employeeDisplaySection = document.getElementById('employee_display_section');
    const projectSection = document.getElementById('project_section');
    const transactionType = document.getElementById('transaction_type');
    const transactionTypeSection = document.getElementById('transaction_type_section');
    const transactionTypeInfoEl = document.getElementById('transaction_type_info');
    const transactionTypeInfoWrapper = document.getElementById('transaction_type_info_wrapper');
    const categorySection = document.getElementById('category_section');
    const assignFields = document.getElementById('assign_fields');
    const returnFields = document.getElementById('return_fields');
    const assetStatusInfo = document.getElementById('asset_status_info');
    const employeeAutoFillInfo = document.getElementById('employee_auto_fill_info');
    const projectAutoFillInfo = document.getElementById('project_auto_fill_info');
    const assetSelectionSection = document.getElementById('asset_selection_section');

    let currentCategory = '';
    let currentAssignmentType = 'employee'; // 'employee' or 'project' – from category dropdown
    let assetDetails = null;

    // Employee autocomplete - type ID or name to search
    const employeeSearch = document.getElementById('employee_search');
    const employeeDropdown = document.getElementById('employee_dropdown');
    const employeeSearchWrap = document.getElementById('employee_search_wrap');
    let employeeDebounce = null;
    if (employeeSearch && employeeDropdown) {
        function hideEmployeeDropdown() {
            employeeDropdown.style.display = 'none';
            employeeDropdown.innerHTML = '';
        }
        function showEmployeeDropdown(items) {
            if (!items || items.length === 0) {
                employeeDropdown.innerHTML = '<div class="list-group-item text-muted">No employees found</div>';
            } else {
                employeeDropdown.innerHTML = items.map(function(emp) {
                    const name = emp.name || emp.entity_name || 'N/A';
                    const extra = [emp.employee_id, emp.department_name, emp.designation].filter(Boolean).join(' · ');
                    const label = '<div class="fw-semibold">' + (name.replace(/</g, '&lt;').replace(/>/g, '&gt;')) + '</div>' + (extra ? '<small class="text-muted">' + (extra.replace(/</g, '&lt;').replace(/>/g, '&gt;')) + '</small>' : '');
                    return '<a href="#" class="list-group-item list-group-item-action employee-suggestion" data-id="' + emp.id + '" data-name="' + (name.replace(/"/g, '&quot;')) + '" data-employee-id="' + (emp.employee_id || '').replace(/"/g, '&quot;') + '">' + label + '</a>';
                }).join('');
            }
            employeeDropdown.style.display = 'block';
        }
        employeeSearch.addEventListener('input', function() {
            const q = (employeeSearch.value || '').trim();
            clearTimeout(employeeDebounce);
            document.getElementById('employee_id').value = '';
            if (q.length < 1) {
                hideEmployeeDropdown();
                return;
            }
            employeeDebounce = setTimeout(function() {
                employeeDropdown.innerHTML = '<div class="list-group-item text-muted">Loading...</div>';
                employeeDropdown.style.display = 'block';
                fetch('{{ route("employees.autocomplete") }}?query=' + encodeURIComponent(q), { credentials: 'same-origin' })
                    .then(function(r) { return r.ok ? r.json() : r.json().then(function(d) { throw d.error || 'Error'; }); })
                    .then(function(data) {
                        if (Array.isArray(data)) showEmployeeDropdown(data);
                        else hideEmployeeDropdown();
                    })
                    .catch(function() {
                        employeeDropdown.innerHTML = '<div class="list-group-item text-danger">Error loading suggestions</div>';
                    });
            }, 200);
        });
        employeeSearch.addEventListener('focus', function() {
            const q = (employeeSearch.value || '').trim();
            if (q.length >= 1 && employeeDropdown.innerHTML && employeeDropdown.style.display !== 'none') {
                employeeDropdown.style.display = 'block';
            }
        });
        employeeDropdown.addEventListener('click', function(e) {
            const item = e.target.closest('.employee-suggestion');
            if (item) {
                e.preventDefault();
                const id = item.getAttribute('data-id');
                const name = item.getAttribute('data-name') || '';
                const empId = item.getAttribute('data-employee-id') || '';
                document.getElementById('employee_id').value = id;
                employeeSearch.value = empId ? (name + ' (' + empId + ')') : name;
                hideEmployeeDropdown();
            }
        });
        document.addEventListener('click', function(e) {
            if (employeeSearchWrap && !employeeSearchWrap.contains(e.target)) hideEmployeeDropdown();
        });
        employeeSearch.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') hideEmployeeDropdown();
        });
    }

    // Step 1: Transaction Type change → show/hide Category
    transactionType.addEventListener('change', function() {
        const txType = this.value;
        categorySection.style.display = txType ? 'block' : 'none';
        assetSelectionSection.style.display = 'none';
        if (assetIdInput) assetIdInput.value = '';
        if (assetSearchInput) assetSearchInput.value = '';
        if (assetDropdownEl) { assetDropdownEl.innerHTML = ''; assetDropdownEl.style.display = 'none'; }
        if (!txType) {
            categoryDropdown.value = '';
            hideAssignmentFields();
            return;
        }
        hideAssignmentFields();
    });

    // On load: if edit or prefilled, show category and asset sections
    (function initFromExisting() {
        if (transactionType && transactionType.value) {
            if (categorySection) categorySection.style.display = 'block';
            if (categoryDropdown && categoryDropdown.value) {
                const opt = categoryDropdown.options[categoryDropdown.selectedIndex];
                currentAssignmentType = (opt && opt.getAttribute('data-assignment-type')) || 'employee';
                if (assetSelectionSection) assetSelectionSection.style.display = 'block';
                categoryDropdown.dispatchEvent(new Event('change'));
            }
        }
    })();

    // Step 2: Category change → show Asset search (text input with autocomplete)
    categoryDropdown.addEventListener('change', function() {
        const categoryId = this.value;
        const selectedOpt = this.options[this.selectedIndex];
        currentAssignmentType = (selectedOpt && selectedOpt.getAttribute('data-assignment-type')) || 'employee';
        currentCategory = '';

        if (!categoryId) {
            assetSelectionSection.style.display = 'none';
            if (assetIdInput) assetIdInput.value = '';
            if (assetSearchInput) assetSearchInput.value = '';
            if (assetDropdownEl) { assetDropdownEl.innerHTML = ''; assetDropdownEl.style.display = 'none'; }
            hideAssignmentFields();
            return;
        }

        assetSelectionSection.style.display = 'block';
        assetStatusInfo.textContent = 'Type serial number or asset ID to see matching assets.';
        var form = document.getElementById('transactionForm');
        var preselect = form ? form.getAttribute('data-preselect-asset') : '';
        if (!preselect) {
            if (assetIdInput) assetIdInput.value = '';
            if (assetSearchInput) assetSearchInput.value = '';
        } else {
            fetch('/asset-transactions/get-asset-details/' + preselect)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    assetDetails = data;
                    currentCategory = (data.category_name || '').toLowerCase();
                    updateTransactionTypeOptions(data);
                    showAssignmentFields(data, currentCategory);
                    if (transactionType.value === 'assign') {
                        assignFields.style.display = 'block';
                        updateEmployeeSectionVisibility();
                    }
                })
                .catch(function() {});
        }
    });

    // Asset search: type to get dropdown of similar assets
    var assetDebounce = null;
    if (assetSearchInput && assetDropdownEl) {
        function hideAssetDropdown() {
            assetDropdownEl.style.display = 'none';
            assetDropdownEl.innerHTML = '';
        }
        function showAssetSuggestions(items) {
            if (!items || items.length === 0) {
                assetDropdownEl.innerHTML = '<div class="list-group-item text-muted">No matching assets</div>';
            } else {
                assetDropdownEl.innerHTML = items.map(function(asset) {
                    var text = (asset.serial_number || '') + ' (' + (asset.asset_id || '') + ') - Status: ' + (asset.status || '');
                    return '<a href="#" class="list-group-item list-group-item-action asset-suggestion" data-id="' + asset.id + '" data-status="' + (asset.original_status || asset.status || '').replace(/"/g, '&quot;') + '" data-category="' + (asset.category_name || '').replace(/"/g, '&quot;') + '">' + text.replace(/</g, '&lt;') + '</a>';
                }).join('');
            }
            assetDropdownEl.style.display = 'block';
        }
        assetSearchInput.addEventListener('input', function() {
            var q = (assetSearchInput.value || '').trim();
            if (assetIdInput) assetIdInput.value = '';
            clearTimeout(assetDebounce);
            hideAssetDropdown();
            var categoryId = categoryDropdown ? categoryDropdown.value : '';
            if (!categoryId || q.length < 1) return;
            assetDebounce = setTimeout(function() {
                assetDropdownEl.innerHTML = '<div class="list-group-item text-muted">Loading...</div>';
                assetDropdownEl.style.display = 'block';
                fetch('/asset-transactions/get-assets-by-category/' + categoryId + '?q=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(function(assets) {
                        showAssetSuggestions(assets);
                    })
                    .catch(function() {
                        assetDropdownEl.innerHTML = '<div class="list-group-item text-danger">Error loading assets</div>';
                    });
            }, 200);
        });
        assetDropdownEl.addEventListener('click', function(e) {
            var item = e.target.closest('.asset-suggestion');
            if (item) {
                e.preventDefault();
                var id = item.getAttribute('data-id');
                var status = item.getAttribute('data-status') || '';
                var category = item.getAttribute('data-category') || '';
                var displayStatus = status === 'returned' ? 'available' : status;
                assetSearchInput.value = item.textContent.trim();
                if (assetIdInput) assetIdInput.value = id;
                hideAssetDropdown();
                currentCategory = category.toLowerCase();
                assetStatusInfo.textContent = 'Current Status: ' + displayStatus;
                assetStatusInfo.className = 'text-muted';
                fetch('/asset-transactions/get-asset-details/' + id)
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        assetDetails = data;
                        updateTransactionTypeOptions(data);
                        showAssignmentFields(data, currentCategory);
                        if (transactionType.value === 'assign') {
                            assignFields.style.display = 'block';
                            updateEmployeeSectionVisibility();
                        }
                    })
                    .catch(function(err) { console.error('Error loading asset details:', err); });
            }
        });
        document.addEventListener('click', function(e) {
            if (assetSearchWrap && !assetSearchWrap.contains(e.target)) hideAssetDropdown();
        });
        assetSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') hideAssetDropdown();
        });
    }

    // Handle transaction type change
    transactionType.addEventListener('change', function() {
        const txType = this.value;
        
        assignFields.style.display = 'none';
        returnFields.style.display = 'none';

        if (!txType) {
            // Reset employee section visibility based on asset status
            updateEmployeeSectionVisibility();
            return;
        }

        if (txType === 'assign') {
            assignFields.style.display = 'block';
            var entityLocSection = document.getElementById('entity_location_section');
            if (entityLocSection) entityLocSection.style.display = 'block';
            updateEmployeeSectionVisibility();
        } else if (txType === 'return') {
            returnFields.style.display = 'block';
            var entityLocSection = document.getElementById('entity_location_section');
            if (entityLocSection) entityLocSection.style.display = 'none';
            employeeSection.style.display = 'none';
            employeeAutoFillInfo.textContent = '';
            updateEmployeeSectionVisibility();

            const employeeIdField = document.getElementById('employee_id');
            const employeeReturnField = document.getElementById('employee_id_return');
            const assetId = document.getElementById('asset_id') ? document.getElementById('asset_id').value : '';

            if (!assetDetails && assetId) {
                fetch(`/asset-transactions/get-asset-details/${assetId}`)
                    .then(res => res.json())
                    .then(data => {
                        assetDetails = data;
                        if (data.current_employee_id) {
                            employeeDisplaySection.style.display = 'block';
                            document.getElementById('display_employee_name').textContent = data.current_employee_name || 'N/A';
                            document.getElementById('display_employee_id').textContent = data.current_employee_id || 'N/A';
                            document.getElementById('display_employee_entity').textContent = data.current_employee_entity || 'N/A';
                            if (employeeIdField) employeeIdField.value = data.current_employee_id;
                            if (employeeReturnField) employeeReturnField.value = data.current_employee_id;
                        } else {
                            employeeDisplaySection.style.display = 'none';
                            if (employeeIdField) employeeIdField.value = '';
                            if (employeeReturnField) employeeReturnField.value = '';
                        }
                        updateEmployeeSectionVisibility();
                    })
                    .catch(err => {
                        console.error('Error fetching asset details:', err);
                        employeeDisplaySection.style.display = 'none';
                        if (employeeIdField) employeeIdField.value = '';
                        if (employeeReturnField) employeeReturnField.value = '';
                    });
            } else if (!assetId) {
                employeeDisplaySection.style.display = 'none';
                if (employeeIdField) employeeIdField.value = '';
                if (employeeReturnField) employeeReturnField.value = '';
            }
        }
    });

    function showAssignmentFields(data, category) {
        if (transactionTypeInfoWrapper) transactionTypeInfoWrapper.style.display = 'block';
        
        const categoryLower = category.toLowerCase();
        const txType = transactionType.value;
        
        // For return transactions, show employee details automatically
        if (txType === 'return' && data.current_employee_id) {
            employeeDisplaySection.style.display = 'block';
            employeeSection.style.display = 'none';
            document.getElementById('display_employee_name').textContent = data.current_employee_name || 'N/A';
            document.getElementById('display_employee_id').textContent = data.current_employee_id || 'N/A';
            document.getElementById('display_employee_entity').textContent = data.current_employee_entity || 'N/A';
            // Set both the select field and hidden field for return transactions
            document.getElementById('employee_id').value = data.current_employee_id;
            document.getElementById('employee_id_return').value = data.current_employee_id;
        } else {
            employeeDisplaySection.style.display = 'none';
        }
        
        // Show fields based on category
        if (categoryLower === 'laptop' && txType === 'assign') {
            // No auto-fill: user types and selects from dropdown
            document.getElementById('employee_id').value = '';
            if (employeeSearch) employeeSearch.value = '';
            employeeAutoFillInfo.textContent = 'Type letters to search and select an employee from the dropdown';
            employeeAutoFillInfo.className = 'text-muted';
        } else if (categoryLower === 'printer') {
            if (data.current_project_name && txType === 'assign') {
                document.getElementById('project_name').value = data.current_project_name;
                projectAutoFillInfo.textContent = `Auto-filled: ${data.current_project_name}`;
                projectAutoFillInfo.className = 'text-success';
            }
        }
        
        // Update visibility based on transaction type and category
        updateEmployeeSectionVisibility();
        
        // If transaction type is 'assign', show assign fields (Assigned Date and Asset Image) and Entity/Location
        if (txType === 'assign') {
            assignFields.style.display = 'block';
            var entityLocSection = document.getElementById('entity_location_section');
            if (entityLocSection) entityLocSection.style.display = 'block';
            // Pre-fill entity & location from asset details when available
            if (data.location_entity || data.current_location_id) {
                var assignEntity = document.getElementById('assign_entity');
                var assignLocation = document.getElementById('assign_location');
                if (assignEntity && data.location_entity) {
                    assignEntity.value = data.location_entity;
                    onAssignEntityChange();
                    if (assignLocation && data.current_location_id) {
                        setTimeout(function() {
                            assignLocation.value = data.current_location_id;
                            if (document.getElementById('location_id')) document.getElementById('location_id').value = data.current_location_id;
                        }, 300);
                    }
                } else if (data.current_location_id && document.getElementById('location_id')) {
                    document.getElementById('location_id').value = data.current_location_id;
                }
            }
        } else if (txType === 'return') {
            returnFields.style.display = 'block';
            var entityLocSection = document.getElementById('entity_location_section');
            if (entityLocSection) entityLocSection.style.display = 'none';
        }
    }
    
    function onAssignEntityChange(selectLocationId) {
        var entity = document.getElementById('assign_entity');
        var locationSelect = document.getElementById('assign_location');
        var locationIdInput = document.getElementById('location_id');
        if (!entity || !locationSelect) return;
        var entityName = (entity.value || '').trim();
        locationSelect.innerHTML = '<option value="">Loading...</option>';
        if (!selectLocationId && locationIdInput) locationIdInput.value = '';
        if (!entityName) {
            locationSelect.innerHTML = '<option value="">-- Select Entity first --</option>';
            return;
        }
        var url = '/asset-transactions/get-locations?entity=' + encodeURIComponent(entityName);
        console.log('Fetching locations for entity:', entityName, '| URL:', url);
        fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(r) {
                if (!r.ok) {
                    throw new Error('HTTP ' + r.status);
                }
                return r.json();
            })
            .then(function(locs) {
                if (!locs || locs.length === 0) {
                    locationSelect.innerHTML = '<option value="">No locations found for this entity</option>';
                    return;
                }
                locationSelect.innerHTML = '<option value="">-- Select Location --</option>';
                (locs || []).forEach(function(loc) {
                    var opt = document.createElement('option');
                    opt.value = loc.id;
                    opt.textContent = (loc.location_name || '') + (loc.location_entity ? ' (' + loc.location_entity + ')' : '');
                    if (selectLocationId && loc.id == selectLocationId) {
                        opt.selected = true;
                    }
                    locationSelect.appendChild(opt);
                });
                if (selectLocationId && locationIdInput) {
                    locationIdInput.value = selectLocationId;
                }
            })
            .catch(function(err) {
                console.error('Error loading locations:', err);
                locationSelect.innerHTML = '<option value="">Error loading locations</option>';
            });
    }
    
    function updateEmployeeSectionVisibility() {
        const txType = transactionType.value;
        const categoryLower = currentCategory.toLowerCase();
        const useProject = (currentAssignmentType === 'project');

        if (txType === 'return') {
            // Hide employee dropdown and project for return
            employeeSection.style.display = 'none';
            projectSection.style.display = 'none';
            const employeeIdField = document.getElementById('employee_id');
            const employeeReturnField = document.getElementById('employee_id_return');
            document.getElementById('project_name').value = '';
            document.getElementById('project_name').required = false;
            employeeAutoFillInfo.textContent = '';
            projectAutoFillInfo.textContent = '';

            // Show employee display section if asset has assigned employee
            if (assetDetails && assetDetails.current_employee_id) {
                employeeDisplaySection.style.display = 'block';
                document.getElementById('display_employee_name').textContent = assetDetails.current_employee_name || 'N/A';
                document.getElementById('display_employee_id').textContent = assetDetails.current_employee_id || 'N/A';
                document.getElementById('display_employee_entity').textContent = assetDetails.current_employee_entity || 'N/A';
                if (employeeIdField) {
                    employeeIdField.value = assetDetails.current_employee_id;
                    employeeIdField.required = false;
                }
                if (employeeReturnField) {
                    employeeReturnField.value = assetDetails.current_employee_id;
                }
            } else {
                employeeDisplaySection.style.display = 'none';
                if (employeeIdField) employeeIdField.value = '';
                if (employeeReturnField) employeeReturnField.value = '';
            }
        } else if (txType === 'assign') {
            employeeDisplaySection.style.display = 'none';
            assignFields.style.display = 'block';

            if (useProject) {
                employeeSection.style.display = 'none';
                if (projectSection) projectSection.style.display = 'block';
                document.getElementById('employee_id').required = false;
                if (document.getElementById('employee_required')) document.getElementById('employee_required').style.display = 'none';
                if (document.getElementById('project_name')) document.getElementById('project_name').required = true;
                if (document.getElementById('project_required')) document.getElementById('project_required').style.display = 'inline';
            } else {
                employeeSection.style.display = 'block';
                if (projectSection) projectSection.style.display = 'none';
                document.getElementById('employee_id').required = true;
                if (document.getElementById('employee_required')) document.getElementById('employee_required').style.display = 'inline';
                if (document.getElementById('project_name')) document.getElementById('project_name').required = false;
                if (document.getElementById('project_required')) document.getElementById('project_required').style.display = 'none';
            }
        }
    }

    function updateTransactionTypeOptions(data) {
        // Transaction type already selected at top; only update the info message below asset
        const infoEl = transactionTypeInfoEl || document.getElementById('transaction_type_info');
        if (!infoEl) return;
        const displayStatus = (data.status === 'returned') ? 'available' : (data.status || '');
        if (displayStatus === 'under_maintenance') {
            infoEl.textContent = 'Asset is under maintenance. You can assign it (return from maintenance to same employee).';
            infoEl.className = 'text-warning';
        } else if (displayStatus === 'assigned') {
            infoEl.textContent = 'Asset is currently assigned. You can return it. Use System Maintenance form to send for maintenance.';
            infoEl.className = 'text-info';
        } else {
            infoEl.textContent = 'Asset is available for assignment to a new employee.';
            infoEl.className = 'text-success';
        }
        updateEmployeeSectionVisibility();
    }

    function hideAssignmentFields() {
        employeeSection.style.display = 'none';
        if (projectSection) projectSection.style.display = 'none';
        if (document.getElementById('project_name')) document.getElementById('project_name').required = false;
        if (transactionTypeInfoWrapper) transactionTypeInfoWrapper.style.display = 'none';
        assignFields.style.display = 'none';
        returnFields.style.display = 'none';
        var entityLocSection = document.getElementById('entity_location_section');
        if (entityLocSection) entityLocSection.style.display = 'none';
    }

    function hideAllFields() {
        assetSelectionSection.style.display = 'none';
        hideAssignmentFields();
    }

    // Form submission handler
    console.log('Setting up form submission handler...');
    const form = document.getElementById('transactionForm');
    const submitBtn = document.getElementById('submitBtn');
    
    console.log('Form element:', form);
    console.log('Submit button:', submitBtn);
    
    if (!form) {
        console.error('ERROR: Form element not found!');
    }
    if (!submitBtn) {
        console.error('ERROR: Submit button not found!');
    }
    
    if (form && submitBtn) {
        console.log('Adding submit event listener...');
        
        // Also add click handler to button as backup
        submitBtn.addEventListener('click', function(e) {
            console.log('=== SUBMIT BUTTON CLICKED ===');
            try {
                const txType = transactionType ? transactionType.value : 'unknown';
                console.log('Transaction type:', txType);
                
                // For return, ensure employee_id is set
                if (txType === 'return') {
                    const empSelect = document.getElementById('employee_id');
                    const empReturn = document.getElementById('employee_id_return');
                    console.log('Employee ID select value:', empSelect ? empSelect.value : 'element not found');
                    console.log('Employee ID return value:', empReturn ? empReturn.value : 'element not found');
                    
                    // Try to set employee_id from various sources
                    if (empSelect && empReturn) {
                        if (!empSelect.value && empReturn.value) {
                            empSelect.value = empReturn.value;
                            console.log('Set employee_id from employee_id_return:', empReturn.value);
                        } else if (!empReturn.value && empSelect.value) {
                            empReturn.value = empSelect.value;
                            console.log('Set employee_id_return from employee_id:', empSelect.value);
                        } else if (!empReturn.value && assetDetails && assetDetails.current_employee_id) {
                            empReturn.value = assetDetails.current_employee_id;
                            empSelect.value = assetDetails.current_employee_id;
                            console.log('Set both from assetDetails:', assetDetails.current_employee_id);
                        }
                    }
                }
            } catch (err) {
                console.error('Error in button click handler:', err);
            }
            // Don't prevent default - let form submit
            return true;
        });
        
        form.addEventListener('submit', async function(e) {
            console.log('=== FORM SUBMIT EVENT TRIGGERED ===');
            
            const txType = transactionType ? transactionType.value : '';
            const assetId = document.getElementById('asset_id') ? document.getElementById('asset_id').value : '';
            
            console.log('Transaction type:', txType);
            console.log('Asset ID:', assetId);
            
            // Remove required attributes from hidden fields to prevent browser validation blocking
            const assignFields = document.getElementById('assign_fields');
            const returnFields = document.getElementById('return_fields');
            const issueDate = document.getElementById('issue_date');
            const returnDate = document.getElementById('return_date');
            const assignImage = document.getElementById('assign_image');
            
            if (txType === 'assign') {
                // For assign, make sure assign fields are required (image is optional)
                if (issueDate) issueDate.required = true;
                if (assignImage) assignImage.required = false;
                // Remove required from return fields
                if (returnDate) returnDate.required = false;
            } else if (txType === 'return') {
                // For return, make sure return fields are required
                if (returnDate) returnDate.required = true;
                // Remove required from assign fields
                if (issueDate) issueDate.required = false;
                if (assignImage) assignImage.required = false;
                
                // Ensure employee_id is set for return
                const employeeSelect = document.getElementById('employee_id');
                const employeeReturn = document.getElementById('employee_id_return');
                
                // Get employee_id from various sources
                let employeeId = null;
                if (employeeReturn && employeeReturn.value) {
                    employeeId = employeeReturn.value;
                } else if (employeeSelect && employeeSelect.value) {
                    employeeId = employeeSelect.value;
                } else if (assetDetails && assetDetails.current_employee_id) {
                    employeeId = assetDetails.current_employee_id;
                }
                
                // If still no employee_id and we have an asset_id, fetch it from the asset
                if (!employeeId && assetId) {
                    console.log('Fetching employee_id from asset...');
                    try {
                        const response = await fetch(`/asset-transactions/get-asset-details/${assetId}`);
                        const data = await response.json();
                        if (data.current_employee_id) {
                            employeeId = data.current_employee_id;
                            // Update assetDetails for future use
                            assetDetails = data;
                            console.log('Fetched employee_id from asset:', employeeId);
                        }
                    } catch (err) {
                        console.error('Error fetching asset details:', err);
                    }
                }
                
                // Set both fields to ensure employee_id is available
                if (employeeId) {
                    if (employeeSelect) {
                        employeeSelect.value = employeeId;
                        employeeSelect.required = false; // Don't require if hidden
                    }
                    if (employeeReturn) employeeReturn.value = employeeId;
                    console.log('Set employee_id for return:', employeeId);
                } else {
                    console.error('ERROR: Cannot find employee_id for return transaction!');
                    console.log('assetDetails:', assetDetails);
                    console.log('assetId:', assetId);
                    e.preventDefault();
                    alert('Error: Cannot determine employee. Please refresh the page and try again.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Save Transaction';
                    }
                    return false;
                }
            }
            
            // Log final form data
            const formData = new FormData(form);
            console.log('=== FINAL FORM DATA ===');
            for (let [key, value] of formData.entries()) {
                console.log(key + ':', value);
            }
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
            }
            
            // Allow form to submit
            console.log('Submitting form to:', form.action);
            console.log('Form method:', form.method);
            return true;
        });
    }

    // Entity & Location: when entity changes load locations; when location changes set hidden location_id
    var assignEntityEl = document.getElementById('assign_entity');
    var assignLocationEl = document.getElementById('assign_location');
    if (assignEntityEl) assignEntityEl.addEventListener('change', onAssignEntityChange);
    if (assignLocationEl) assignLocationEl.addEventListener('change', function() {
        var locId = document.getElementById('assign_location') ? document.getElementById('assign_location').value : '';
        if (document.getElementById('location_id')) document.getElementById('location_id').value = locId || '';
    });

    // Initialize on page load
    if (categoryDropdown.value) {
        categoryDropdown.dispatchEvent(new Event('change'));
    }
    
    // Final test - make sure form can submit
    // Edit mode: pre-fill entity and location, show sections
    var editEntityNameInput = document.getElementById('edit_entity_name');
    var editLocationIdInput = document.getElementById('edit_location_id');
    var assignEntitySelect = document.getElementById('assign_entity');
    var formEl = document.getElementById('transactionForm');
    var preselect = formEl ? formEl.getAttribute('data-preselect-asset') : '';
    console.log('Edit mode check - preselect:', preselect, 'transactionType:', transactionType.value);
    if (preselect && transactionType.value) {
        // This is edit mode - show relevant sections
        categorySection.style.display = 'block';
        assetSelectionSection.style.display = 'block';
        if (transactionType.value === 'assign') {
            assignFields.style.display = 'block';
            var entityLocSection = document.getElementById('entity_location_section');
            if (entityLocSection) entityLocSection.style.display = 'block';
            employeeSection.style.display = 'block';
            // Load locations for the pre-selected entity with a small delay
            setTimeout(function() {
                var entityVal = assignEntitySelect ? assignEntitySelect.value : '';
                var locationToSelect = editLocationIdInput ? editLocationIdInput.value : '';
                console.log('Edit mode: Loading locations for entity:', entityVal, 'selecting location:', locationToSelect);
                if (entityVal) {
                    loadLocationsForEdit(entityVal, locationToSelect);
                }
            }, 100);
        } else if (transactionType.value === 'return') {
            returnFields.style.display = 'block';
        }
    }
    
    // Separate function for edit mode location loading
    function loadLocationsForEdit(entityName, selectLocationId) {
        var locationSelect = document.getElementById('assign_location');
        var locationIdInput = document.getElementById('location_id');
        if (!locationSelect) return;
        
        locationSelect.innerHTML = '<option value="">Loading...</option>';
        var url = '/asset-transactions/get-locations?entity=' + encodeURIComponent(entityName);
        console.log('loadLocationsForEdit - URL:', url);
        
        fetch(url, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(r) {
            console.log('loadLocationsForEdit - Response status:', r.status);
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(locs) {
            console.log('loadLocationsForEdit - Locations received:', locs);
            if (!locs || locs.length === 0) {
                locationSelect.innerHTML = '<option value="">No locations found</option>';
                return;
            }
            locationSelect.innerHTML = '<option value="">-- Select Location --</option>';
            locs.forEach(function(loc) {
                var opt = document.createElement('option');
                opt.value = loc.id;
                opt.textContent = (loc.location_name || '') + (loc.location_entity ? ' (' + loc.location_entity + ')' : '');
                if (selectLocationId && loc.id == selectLocationId) {
                    opt.selected = true;
                }
                locationSelect.appendChild(opt);
            });
            if (selectLocationId && locationIdInput) {
                locationIdInput.value = selectLocationId;
            }
        })
        .catch(function(err) {
            console.error('loadLocationsForEdit - Error:', err);
            locationSelect.innerHTML = '<option value="">Error loading locations</option>';
        });
    }

    console.log('=== FORM SETUP COMPLETE ===');
    console.log('Form found:', !!form);
    console.log('Submit button found:', !!submitBtn);
    console.log('Form action:', form ? form.action : 'N/A');
    
    // Add a direct test button click handler as fallback
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            console.log('=== DIRECT BUTTON CLICK ===');
            console.log('Event:', e);
            
            // If form doesn't submit after 100ms, try programmatic submit
            setTimeout(function() {
                if (!form.checkValidity()) {
                    console.log('Form validation failed, showing validation errors');
                    form.reportValidity();
                } else {
                    console.log('Form is valid, attempting programmatic submit');
                    // Don't prevent default, let normal submit happen
                }
            }, 100);
        }, true); // Use capture phase
    }
    
    // Also add a noValidate attribute to form to bypass HTML5 validation if needed
    // But we'll handle validation manually
    console.log('Form setup complete. Form action:', form ? form.action : 'N/A');
});
</script>
@endsection
