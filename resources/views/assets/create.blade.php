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
    <label>Brand</label>
    <select name="brand_id" id="brand" class="form-control" required>
        <option value="">-- Select Brand --</option>
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
    // When form category changes → load brands, clear features, and generate asset ID
    $('#category').on('change', function () {
        const categoryId = $(this).val();

        $('#brand').html('<option value="">-- Select Brand --</option>');
        $('#feature-fields').html('');  // clear features on category change
        $('input[name="asset_id"]').val('');  // clear asset ID

        if (categoryId) {
            // Generate asset ID based on category
            $.get(`/assets/next-id/${categoryId}`, function (response) {
                if (response.asset_id) {
                    $('input[name="asset_id"]').val(response.asset_id);
                }
            }).fail(function() {
                console.error('Error generating asset ID');
            });
            
            // Load brands for selected category
            $.get(`/brands/by-category/${categoryId}`, function (brands) {
                brands.forEach(function (brand) {
                    $('#brand').append(`<option value="${brand.id}">${brand.name}</option>`);
                });
            });
        }
    });

    $('#brand').on('change', function () {
        const brandId = $(this).val();

        $('#feature-fields').html('');  // clear old features

        if (brandId) {
            // Load features for this brand
            $.get(`/features/by-brand/${brandId}`, function (features) {
                let html = '';
                features.forEach(function (feature) {
                    // Check if feature has sub_fields (like Storage)
                    if (feature.sub_fields && Array.isArray(feature.sub_fields) && feature.sub_fields.length > 0) {
                        // Create a field group with sub-fields
                        html += `
                        <div class="form-group mb-3">
                           <label class="fw-bold">${feature.feature_name}</label>`;
                        
                        feature.sub_fields.forEach(function (subField) {
                            html += `
                            <div class="mb-2">
                                <label class="small text-muted">${subField}</label>
                                <input type="text" name="features[${feature.id}][${subField}]" class="form-control" placeholder="Enter ${subField}">
                            </div>`;
                        });
                        
                        html += `</div>`;
                    } else {
                        // Regular single field
                        html += `
                        <div class="form-group mb-3">
                           <label>${feature.feature_name}</label>
                            <input type="text" name="features[${feature.id}]" class="form-control" required>
                        </div>`;
                    }
                });
                $('#feature-fields').html(html);
            });
        }
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
