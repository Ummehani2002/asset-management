@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="bi bi-funnel me-2"></i>Filter Assets by Category</h2>
        <p>View and filter assets by category</p>
    </div>

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

    {{-- Filter Section --}}
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Store current category ID for download
    let currentCategoryId = null;

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
</script>
@endsection
