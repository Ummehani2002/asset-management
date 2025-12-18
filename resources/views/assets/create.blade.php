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

    <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data">
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
    <input type="text" name="serial_number" id="serial_number" class="form-control" required>
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
        <a href="{{ route('assets.index') }}" class="btn btn-secondary">Cancel</a>
    </form>

    {{-- Filter Section --}}
    <div class="mt-5">
        <div class="master-form-card mb-4">
            <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filter Assets by Category</h5>
            <div class="row">
                <div class="col-md-6">
                    <label for="filter_category" class="form-label">Select Category</label>
                    <select id="filter_category" class="form-control">
                        <option value="">-- Select Category to View Assets --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Assets Table (shown only when category is selected) --}}
        <div id="assets-section" style="display: none;">
            <div class="master-table-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>Assets</h5>
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
                        <table class="table table-bordered mb-0" id="assetsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Asset ID</th>
                                    <th>Brand</th>
                                    <th>Purchase Date</th>
                                    <th>Warranty Start</th>
                                    <th>Expiry Date</th>
                                    <th>PO Number</th>
                                    <th>Serial Number</th>
                                    <th>Features</th>
                                    <th>Invoice</th>
                                    <th>History</th>
                                </tr>
                            </thead>
                            <tbody id="assetsTableBody">
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">Select a category to view assets.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
    // Store current category ID for download (for filter)
    let currentCategoryId = null;

    // When form category changes → load brands and clear features (for form only)
    $('#category').on('change', function () {
        const categoryId = $(this).val();

        $('#brand').html('<option value="">-- Select Brand --</option>');
        $('#feature-fields').html('');  // clear features on category change

        if (categoryId) {
            // Load brands for selected category
            $.get(`/brands/by-category/${categoryId}`, function (brands) {
                brands.forEach(function (brand) {
                    $('#brand').append(`<option value="${brand.id}">${brand.name}</option>`);
                });
            });
        }
    });

    // When filter category changes → load assets
    $('#filter_category').on('change', function () {
        const categoryId = $(this).val();
        currentCategoryId = categoryId;

        if (categoryId) {
            loadAssetsByCategory(categoryId);
        } else {
            $('#assets-section').hide();
            $('#downloadDropdown').hide();
        }
    });

    // Function to load assets by category
    function loadAssetsByCategory(categoryId) {
        $.get(`/api/assets/by-category/${categoryId}`, function(assets) {
            let tableBody = $('#assetsTableBody');
            tableBody.empty();

            if (assets && assets.length > 0) {
                assets.forEach(function(asset, index) {
                    // Format features
                    let featuresHtml = '';
                    if (asset.features && asset.features.length > 0) {
                        featuresHtml = '<ul class="mb-0" style="font-size: 0.85rem;">';
                        asset.features.forEach(function(feature) {
                            featuresHtml += `<li>${feature}</li>`;
                        });
                        featuresHtml += '</ul>';
                    } else {
                        featuresHtml = '<em class="text-muted">No features</em>';
                    }

                    // Invoice link
                    let invoiceHtml = 'N/A';
                    if (asset.invoice_path) {
                        invoiceHtml = `<a href="/storage/${asset.invoice_path}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-pdf"></i> View
                        </a>`;
                    }

                    // History link
                    let historyHtml = '';
                    if (asset.id) {
                        historyHtml = `<a href="/asset-history/${asset.id}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-clock-history"></i> View
                        </a>`;
                    }

                    tableBody.append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${asset.asset_id || 'N/A'}</td>
                            <td>${asset.brand_name || 'N/A'}</td>
                            <td>${asset.purchase_date || 'N/A'}</td>
                            <td>${asset.warranty_start || 'N/A'}</td>
                            <td>${asset.expiry_date || 'N/A'}</td>
                            <td>${asset.po_number || 'N/A'}</td>
                            <td>${asset.serial_number || 'N/A'}</td>
                            <td>${featuresHtml}</td>
                            <td>${invoiceHtml}</td>
                            <td>${historyHtml}</td>
                        </tr>
                    `);
                });
                $('#assets-section').show();
                $('#downloadDropdown').show();
            } else {
                tableBody.append('<tr><td colspan="11" class="text-center text-muted py-4">No assets found in this category.</td></tr>');
                $('#assets-section').show();
                $('#downloadDropdown').hide();
            }
        }).fail(function() {
            $('#assets-section').hide();
            $('#downloadDropdown').hide();
        });
    }

    // Download handlers
    $('#downloadPdf').on('click', function(e){
        e.preventDefault();
        if (currentCategoryId) {
            window.location.href = `/assets/category/${currentCategoryId}/export?format=pdf`;
        }
    });

    $('#downloadCsv').on('click', function(e){
        e.preventDefault();
        if (currentCategoryId) {
            window.location.href = `/assets/category/${currentCategoryId}/export?format=csv`;
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
