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
    <label>Category</label>
    <select name="asset_category_id" id="category" class="form-control" required>
        <option value="">-- Select Category --</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
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
    <label>Model <span class="text-muted">(select to autofill brand and all features)</span></label>
    <select name="brand_model_id" id="brand_model" class="form-control">
        <option value="">-- Select Model --</option>
    </select>
</div>

<div class="mb-3" id="brand-wrap">
    <label>Brand <span class="text-muted small">(auto-set from model)</span></label>
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
    // When category changes → load models by category, load brands (for dropdown), clear features
    $('#category').on('change', function () {
        const categoryId = $(this).val();

        $('#brand').html('<option value="">-- Select Model first --</option>');
        $('#brand_model').html('<option value="">-- Select Model --</option>');
        $('#feature-fields').html('');
        $('input[name="asset_id"]').val('');

        if (categoryId) {
            $.get(`/assets/next-id/${categoryId}`, function (response) {
                if (response.asset_id) $('input[name="asset_id"]').val(response.asset_id);
            }).fail(function() { console.error('Error generating asset ID'); });

            // Load all models for this category (Brand - Model number)
            $.get(`/models-by-category/${categoryId}`, function (models) {
                models.forEach(function (m) {
                    $('#brand_model').append($('<option></option>').attr('value', m.id).attr('data-brand-id', m.brand_id).text((m.brand_name || '') + ' - ' + (m.model_number || '')));
                });
            });
            // Load brands for this category (so we can set brand when model is selected)
            $.get(`/brands/by-category/${categoryId}`, function (brands) {
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
        $.get(`/features/by-brand/${brandId}`, function (features) {
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
            $.get(`/model-feature-values/${modelId}`, function (values) {
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
        $.get(`/features/by-brand/${brandId}`, function (features) {
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
        const startDate = $('#warranty_start').val();
        const years = parseInt($('#warranty_years').val());

        if (startDate && years && years > 0) {
            const date = new Date(startDate);
            date.setFullYear(date.getFullYear() + years);

            const yyyy = date.getFullYear();
            let mm = (date.getMonth() + 1).toString().padStart(2, '0');
            let dd = date.getDate().toString().padStart(2, '0');

            $('#expiry_date').val(`${yyyy}-${mm}-${dd}`);
        } else {
            $('#expiry_date').val('');
        }
    }

    $('#warranty_start, #warranty_years').on('change keyup', calculateExpiry);

    
    $(document).ready(calculateExpiry);

</script>
@endsection
