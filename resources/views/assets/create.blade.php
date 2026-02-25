@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Add New Asset</h2>
    
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
        </ul>
    </div>
@endif

    <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
        @csrf

               <div class="mb-3">
    <label>Entity <span class="text-danger">*</span></label>
    <select name="entity_id" id="entity" class="form-control" required>
        <option value="">-- Select Entity --</option>
        @if(isset($entities))
        @foreach($entities as $entity)
            <option value="{{ $entity->id }}">{{ ucwords($entity->name) }}</option>
        @endforeach
        @endif
    </select>
</div>

               <div class="mb-3">
    <label>Category</label>
    <select name="asset_category_id" id="category" class="form-control" required>
        <option value="" data-category-name="">-- Select Category --</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" data-category-name="{{ strtolower($cat->category_name) }}">{{ $cat->category_name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
            <label>Asset ID (Auto-generated)</label>
            <input type="text" name="asset_id" class="form-control" value="{{ $autoAssetId }}" readonly>
        </div>

    <div class="form-group">
    <label for="serial_number">Serial Number</label>
    <input type="text" name="serial_number" id="serial_number" class="form-control" required autocomplete="off">
</div>

<div class="mb-3">
    <label>Model <span class="text-muted"></span></label>
    <select name="brand_model_id" id="brand_model" class="form-control">
        <option value="">-- Select Model --</option>
    </select>
</div>

<div class="mb-3" id="brand-wrap">
    <label>Brand <span class="text-muted small"></span></label>
    <select name="brand_id" id="brand" class="form-control" required>
        <option value="">-- Select Model first --</option>
    </select>
</div>

        <div id="feature-fields"></div>

        <div class="mb-3">
            <label>Purchase Date</label>
            <input type="date" name="purchase_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Warranty Start</label>
            <input type="date" name="warranty_start" id="warranty_start" class="form-control" required>
        </div>

        
        <div class="mb-3">
            <label>Warranty (Years)</label>
            <input type="number" id="warranty_years" name="warranty_years" class="form-control" min="1" placeholder="e.g. 3" required>
        </div>

        <div class="mb-3">
            <label>Expiry Date (Auto)</label>
            <input type="date" name="expiry_date" id="expiry_date" class="form-control" readonly required>
        </div>

        <div class="mb-3">
            <label>PO Number</label>
            <input type="text" name="po_number" class="form-control">
        </div>

        <div class="mb-3">
            <label>Vendor Name</label>
            <input type="text" name="vendor_name" class="form-control" placeholder="Enter vendor name">
        </div>

        <div class="mb-3">
            <label>Value</label>
            <input type="number" name="value" class="form-control" step="0.01" min="0" placeholder="Enter value">
        </div>

        {{-- Laptop-only: Patch / Antivirus / AutoCAD = Yes/No only; OS / MS Office / On-Screen Takeoff = Yes/No then value field --}}
        <div id="laptop-license-fields" class="border rounded p-3 mb-3 bg-light" style="display: none;">
            <h6 class="mb-3"><i class="bi bi-laptop me-2"></i>License & Software</h6>

            {{-- Yes/No only (no value field) --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Patch Management</label>
                    <div class="d-flex gap-3">
                        <label class="d-flex align-items-center gap-1"><input type="radio" name="patch_management_software" value="Yes"> Yes</label>
                        <label class="d-flex align-items-center gap-1"><input type="radio" name="patch_management_software" value="No" checked> No</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Antivirus</label>
                    <div class="d-flex gap-3">
                        <label class="d-flex align-items-center gap-1"><input type="radio" name="antivirus_license_version" value="Yes"> Yes</label>
                        <label class="d-flex align-items-center gap-1"><input type="radio" name="antivirus_license_version" value="No" checked> No</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">AutoCAD</label>
                    <div class="d-flex gap-3">
                        <label class="d-flex align-items-center gap-1"><input type="radio" name="autocad_license_key" value="Yes"> Yes</label>
                        <label class="d-flex align-items-center gap-1"><input type="radio" name="autocad_license_key" value="No" checked> No</label>
                    </div>
                </div>
            </div>

            {{-- OS License Key: Yes/No then value --}}
            <div class="mb-3">
                <label class="form-label">OS License Key</label>
                <div class="d-flex gap-3 mb-2">
                    <label class="d-flex align-items-center gap-1"><input type="radio" name="has_os_license" id="has_os_yes" value="yes"> Yes</label>
                    <label class="d-flex align-items-center gap-1"><input type="radio" name="has_os_license" id="has_os_no" value="no" checked> No</label>
                </div>
                <div id="os_license_value_wrap" style="display: none;">
                    <input type="text" name="os_license_key" id="os_license_key" class="form-control" placeholder="Enter OS license key">
                </div>
            </div>

            {{-- MS Office License Key: Yes/No then value --}}
            <div class="mb-3">
                <label class="form-label">MS Office License Key</label>
                <div class="d-flex gap-3 mb-2">
                    <label class="d-flex align-items-center gap-1"><input type="radio" name="has_ms_office_license" id="has_ms_office_yes" value="yes"> Yes</label>
                    <label class="d-flex align-items-center gap-1"><input type="radio" name="has_ms_office_license" id="has_ms_office_no" value="no" checked> No</label>
                </div>
                <div id="ms_office_license_value_wrap" style="display: none;">
                    <input type="text" name="ms_office_license_key" id="ms_office_license_key" class="form-control" placeholder="Enter MS Office license key">
                </div>
            </div>

            {{-- On-Screen Takeoff Key: Yes/No then value --}}
            <div class="mb-3">
                <label class="form-label">On-Screen Takeoff Key</label>
                <div class="d-flex gap-3 mb-2">
                    <label class="d-flex align-items-center gap-1"><input type="radio" name="has_takeoff_key" id="has_takeoff_yes" value="yes"> Yes</label>
                    <label class="d-flex align-items-center gap-1"><input type="radio" name="has_takeoff_key" id="has_takeoff_no" value="no" checked> No</label>
                </div>
                <div id="takeoff_key_value_wrap" style="display: none;">
                    <input type="text" name="on_screen_takeoff_key" id="on_screen_takeoff_key" class="form-control" placeholder="Enter On-Screen Takeoff key">
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label>Upload Invoice</label>
            <input type="file" name="invoice" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            <small class="text-muted">Optional - PDF, JPG, JPEG, PNG (Max 10MB)</small>
        </div>

        <button type="submit" class="btn btn-primary" id="submitBtn">Save Asset</button>
        <button type="button" class="btn btn-secondary ms-2" onclick="resetForm(this)">
            <i class="bi bi-x-circle me-2"></i>Cancel
        </button>
    </form>
</div>

<script>
// Add form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="{{ route('assets.store') }}"]');
    const submitBtn = document.getElementById('submitBtn');
    
    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Saving...';
            
            // Allow form to submit
            return true;
        });
    }
});
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // When category changes → load models by category, load brands (for dropdown), clear features, show/hide laptop fields
    $('#category').on('change', function () {
        const categoryId = $(this).val();
        const opt = $(this).find('option:selected');
        const categoryName = opt.data('category-name') || '';

        $('#brand').html('<option value="">-- Select Model first --</option>');
        $('#brand_model').html('<option value="">-- Select Model --</option>');
        $('#feature-fields').html('');
        $('input[name="asset_id"]').val('');

        // Show laptop license block only for Laptop category
        if (categoryName === 'laptop') {
            $('#laptop-license-fields').show();
            $('#has_os_no, #has_ms_office_no, #has_takeoff_no').prop('checked', true);
            $('#os_license_value_wrap, #ms_office_license_value_wrap, #takeoff_key_value_wrap').hide();
            $('#os_license_key, #ms_office_license_key, #on_screen_takeoff_key').val('');
        } else {
            $('#laptop-license-fields').hide();
            $('#os_license_value_wrap, #ms_office_license_value_wrap, #takeoff_key_value_wrap').hide();
            $('#os_license_key, #ms_office_license_key, #on_screen_takeoff_key').val('');
        }

        if (!categoryId) {
            $('#laptop-license-fields').hide();
            $('#os_license_value_wrap, #ms_office_license_value_wrap, #takeoff_key_value_wrap').hide();
        }

        if (categoryId) {
            $.get(`{{ url('assets/next-id') }}/${categoryId}`, function (response) {
                if (response.asset_id) $('input[name="asset_id"]').val(response.asset_id);
            }).fail(function(xhr) { console.error('Error generating asset ID', xhr.status, xhr.responseText); });

            // Load all models for this category (Brand - Model number)
            $.get(`{{ url('models-by-category') }}/${categoryId}`, function (models) {
                models.forEach(function (m) {
                    $('#brand_model').append($('<option></option>').attr('value', m.id).attr('data-brand-id', m.brand_id).text((m.brand_name || '') + ' - ' + (m.model_number || '')));
                });
            }).fail(function(xhr) { console.error('Error loading models', xhr.status, xhr.responseText); });
            // Load brands for this category (so we can set brand when model is selected)
            $.get(`{{ url('brands/by-category') }}/${categoryId}`, function (brands) {
                $('#brand').html('<option value="">-- Select Model first --</option>');
                brands.forEach(function (b) {
                    $('#brand').append($('<option></option>').attr('value', b.id).text(b.name));
                });
            });
        }
    });

    var settingBrandFromModel = false;
    // When model is selected → set brand, load feature fields, then fill with model's feature values
    $('#brand_model').on('change', function () {
        const opt = $(this).find('option:selected');
        const modelId = opt.val();
        const brandId = opt.data('brand-id');

        $('#feature-fields').html('');
        if (!modelId || !brandId) {
            $('#brand').val('');
            return;
        }
        settingBrandFromModel = true;
        $('#brand').val(brandId);

        // Load features for this brand (renders the inputs)
        $.get(`{{ url('features/by-brand') }}/${brandId}`, function (features) {
            let html = '';
            features.forEach(function (feature) {
                if (feature.sub_fields && Array.isArray(feature.sub_fields) && feature.sub_fields.length > 0) {
                    html += `<div class="form-group mb-3" data-feature-id="${feature.id}" data-has-sub="1"><label class="fw-bold">${feature.feature_name}</label>`;
                    feature.sub_fields.forEach(function (subField) {
                        html += `<div class="mb-2"><label class="small text-muted">${subField}</label><input type="text" name="features[${feature.id}][${subField}]" class="form-control feature-input" data-feature-id="${feature.id}" data-sub="${subField}" placeholder="Enter ${subField}"></div>`;
                    });
                    html += `</div>`;
                } else {
                    html += `<div class="form-group mb-3" data-feature-id="${feature.id}"><label>${feature.feature_name}</label><input type="text" name="features[${feature.id}]" class="form-control feature-input" data-feature-id="${feature.id}" required></div>`;
                }
            });
            $('#feature-fields').html(html);

            // Now fill with model's saved feature values and make fields read-only
            $.get(`{{ url('model-feature-values') }}/${modelId}`, function (values) {
                if (!values || typeof values !== 'object') { settingBrandFromModel = false; return; }
                $.each(values, function (featureId, val) {
                    if (typeof val === 'string') {
                        $('input.feature-input[data-feature-id="' + featureId + '"]:not([data-sub])').val(val);
                    } else if (typeof val === 'object' && val !== null) {
                        $.each(val, function (subKey, subVal) {
                            $('input.feature-input[data-feature-id="' + featureId + '"][data-sub="' + subKey + '"]').val(subVal);
                        });
                    }
                });
                $('#feature-fields input.feature-input').prop('readonly', true).addClass('bg-light');
                $('#feature-fields').prepend('<p class="small text-muted mb-2"><i class="bi bi-lock me-1"></i>Values from selected model (read-only).</p>');
                settingBrandFromModel = false;
            });
        });
    });

    // When brand is changed manually (e.g. no model selected) → load feature fields only
    $('#brand').on('change', function () {
        if (settingBrandFromModel) return;
        const brandId = $(this).val();
        if (brandId) $('#brand_model').val(''); // clear model when brand changed manually
        $('#feature-fields').html('');
        if (!brandId) return;
        $.get(`{{ url('features/by-brand') }}/${brandId}`, function (features) {
            let html = '';
            features.forEach(function (feature) {
                if (feature.sub_fields && Array.isArray(feature.sub_fields) && feature.sub_fields.length > 0) {
                    html += `<div class="form-group mb-3" data-feature-id="${feature.id}" data-has-sub="1"><label class="fw-bold">${feature.feature_name}</label>`;
                    feature.sub_fields.forEach(function (subField) {
                        html += `<div class="mb-2"><label class="small text-muted">${subField}</label><input type="text" name="features[${feature.id}][${subField}]" class="form-control feature-input" data-feature-id="${feature.id}" data-sub="${subField}" placeholder="Enter ${subField}"></div>`;
                    });
                    html += `</div>`;
                } else {
                    html += `<div class="form-group mb-3" data-feature-id="${feature.id}"><label>${feature.feature_name}</label><input type="text" name="features[${feature.id}]" class="form-control feature-input" data-feature-id="${feature.id}" required></div>`;
                }
            });
            $('#feature-fields').html(html);
        });
    });


    // Calculate expiry date based on warranty_start + warranty_years
    function calculateExpiry() {
        const startInput = document.getElementById('warranty_start');
        const yearsInput = document.getElementById('warranty_years');
        const expiryInput = document.getElementById('expiry_date');
        if (!startInput || !yearsInput || !expiryInput) return;

        const startDate = startInput.value;
        const years = parseInt(yearsInput.value, 10);

        if (startDate && !isNaN(years) && years > 0) {
            const parts = startDate.split('-');
            if (parts.length === 3) {
                const y = parseInt(parts[0], 10);
                const m = parseInt(parts[1], 10) - 1;
                const d = parseInt(parts[2], 10);
                const date = new Date(y, m, d);
                date.setFullYear(date.getFullYear() + years);

                const yyyy = date.getFullYear();
                const mm = (date.getMonth() + 1).toString().padStart(2, '0');
                const dd = date.getDate().toString().padStart(2, '0');
                const expiryVal = yyyy + '-' + mm + '-' + dd;
                expiryInput.value = expiryVal;
            }
        } else {
            expiryInput.value = '';
        }
    }

    $('#warranty_start, #warranty_years').on('change keyup input', calculateExpiry);

    // Hook into Flatpickr for warranty_start (Flatpickr may not fire native change in some cases)
    function hookWarrantyFlatpickr() {
        const el = document.getElementById('warranty_start');
        if (el && el._flatpickr) {
            var orig = el._flatpickr.config.onChange;
            el._flatpickr.config.onChange = function(selDates, dateStr) {
                if (typeof orig === 'function') orig(selDates, dateStr);
                calculateExpiry();
            };
            return true;
        }
        return false;
    }
    $(document).ready(function() {
        calculateExpiry();
        setTimeout(function() { hookWarrantyFlatpickr(); }, 100);

        // OS License Key: show value field only when Yes
        $('input[name="has_os_license"]').on('change', function () {
            if ($(this).val() === 'yes') {
                $('#os_license_value_wrap').show();
            } else {
                $('#os_license_value_wrap').hide();
                $('#os_license_key').val('');
            }
        });
        // MS Office License Key: show value field only when Yes
        $('input[name="has_ms_office_license"]').on('change', function () {
            if ($(this).val() === 'yes') {
                $('#ms_office_license_value_wrap').show();
            } else {
                $('#ms_office_license_value_wrap').hide();
                $('#ms_office_license_key').val('');
            }
        });
        // On-Screen Takeoff Key: show value field only when Yes
        $('input[name="has_takeoff_key"]').on('change', function () {
            if ($(this).val() === 'yes') {
                $('#takeoff_key_value_wrap').show();
            } else {
                $('#takeoff_key_value_wrap').hide();
                $('#on_screen_takeoff_key').val('');
            }
        });
    });

</script>
@endsection
