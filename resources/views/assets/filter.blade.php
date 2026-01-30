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

    {{-- Search by Serial Number --}}
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-search me-2"></i>Search by Serial Number</h5>
        <div class="row">
            <div class="col-md-6 position-relative">
                <label for="serial_search" class="form-label">Type serial number (initial characters)</label>
                <input type="text" id="serial_search" class="form-control" placeholder="e.g. LPT, 55, PRT..." autocomplete="off">
                <div id="serial_dropdown" class="list-group position-absolute w-100 mt-1 border rounded shadow-sm" style="z-index: 1050; max-height: 280px; overflow-y: auto; display: none;"></div>
                <small class="text-muted">Start typing to see matching assets; select one to view details.</small>
            </div>
        </div>
    </div>

    {{-- Asset Details (shown when an asset is selected from serial search) --}}
    <div id="asset-details-section" class="master-form-card mb-4" style="display: none;">
        <h5 class="mb-3"><i class="bi bi-box-seam me-2"></i>Asset Details</h5>
        <div id="asset-details-content" class="card-body"></div>
        <div class="mt-2">
            <a href="#" id="asset-details-history-link" class="btn btn-sm btn-outline-info" style="display: none;"><i class="bi bi-clock-history me-1"></i>View History</a>
        </div>
    </div>

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

    // Serial number search with dropdown
    (function() {
        var input = document.getElementById('serial_search');
        var dropdown = document.getElementById('serial_dropdown');
        var detailsSection = document.getElementById('asset-details-section');
        var detailsContent = document.getElementById('asset-details-content');
        var historyLink = document.getElementById('asset-details-history-link');
        var debounce = null;
        var searchUrl = '{{ url("api/assets/search-serial") }}';
        var detailsUrlBase = '{{ url("get-asset-full-details") }}';
        var historyUrlBase = '{{ url("asset-history") }}';

        function hideDropdown() { if (dropdown) dropdown.style.display = 'none'; }

        function showDropdown(items) {
            if (!dropdown) return;
            dropdown.innerHTML = '';
            if (!items || items.length === 0) {
                dropdown.innerHTML = '<div class="list-group-item text-muted text-center small">No assets found</div>';
            } else {
                items.forEach(function(item) {
                    var a = document.createElement('a');
                    a.href = '#';
                    a.className = 'list-group-item list-group-item-action';
                    a.textContent = (item.serial_number || '') + ' — ' + (item.asset_id || '') + ' (' + (item.category_name || '') + ')';
                    a.dataset.id = item.id;
                    a.dataset.serial = item.serial_number || '';
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (input) input.value = item.serial_number || item.asset_id || '';
                        hideDropdown();
                        loadAssetDetails(item.id);
                    });
                    dropdown.appendChild(a);
                });
            }
            dropdown.style.display = 'block';
        }

        function loadAssetDetails(assetId) {
            if (!assetId) return;
            detailsContent.innerHTML = '<p class="text-muted mb-0">Loading...</p>';
            detailsSection.style.display = 'block';
            $.get(detailsUrlBase + '/' + assetId, function(data) {
                var a = data.asset || {};
                var catName = (a.asset_category && a.asset_category.category_name) || (a.assetCategory && a.assetCategory.category_name) || 'N/A';
                var brandName = (a.brand && a.brand.name) || 'N/A';
                var html = '<div class="row">' +
                    '<div class="col-md-4"><strong>Asset ID</strong><br>' + (a.asset_id || 'N/A') + '</div>' +
                    '<div class="col-md-4"><strong>Serial Number</strong><br>' + (a.serial_number || 'N/A') + '</div>' +
                    '<div class="col-md-4"><strong>Category</strong><br>' + catName + '</div>' +
                    '</div><div class="row mt-2">' +
                    '<div class="col-md-4"><strong>Brand</strong><br>' + brandName + '</div>' +
                    '<div class="col-md-4"><strong>Status</strong><br>' + (a.status || 'N/A') + '</div>' +
                    '<div class="col-md-4"><strong>Purchase Date</strong><br>' + (a.purchase_date || 'N/A') + '</div>' +
                    '</div><div class="row mt-2">' +
                    '<div class="col-md-4"><strong>Warranty Start</strong><br>' + (a.warranty_start || 'N/A') + '</div>' +
                    '<div class="col-md-4"><strong>Expiry Date</strong><br>' + (a.expiry_date || 'N/A') + '</div>' +
                    '<div class="col-md-4"><strong>PO Number</strong><br>' + (a.po_number || 'N/A') + '</div>' +
                    '</div>';
                if (a.invoice_path) {
                    html += '<div class="row mt-2"><div class="col-12"><strong>Invoice</strong><br><a href="/storage/' + a.invoice_path + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-pdf"></i> View</a></div></div>';
                }
                if (data.employee && data.employee.name) {
                    html += '<div class="row mt-2"><div class="col-12"><strong>Assigned To</strong><br>' + data.employee.name + '</div></div>';
                }
                if (data.project && data.project.project_name) {
                    html += '<div class="row mt-2"><div class="col-12"><strong>Project</strong><br>' + data.project.project_name + '</div></div>';
                }
                detailsContent.innerHTML = html;
                if (historyLink) {
                    historyLink.href = historyUrlBase + '/' + assetId;
                    historyLink.style.display = 'inline-block';
                }
            }).fail(function() {
                detailsContent.innerHTML = '<p class="text-danger mb-0">Failed to load asset details.</p>';
                if (historyLink) historyLink.style.display = 'none';
            });
        }

        if (input) {
            input.addEventListener('input', function() {
                clearTimeout(debounce);
                var q = (input.value || '').trim();
                if (q.length < 1) {
                    hideDropdown();
                    detailsSection.style.display = 'none';
                    return;
                }
                debounce = setTimeout(function() {
                    $.get(searchUrl + '?q=' + encodeURIComponent(q), function(items) {
                        showDropdown(items);
                    }).fail(function() { showDropdown([]); });
                }, 200);
            });
            input.addEventListener('blur', function() { setTimeout(hideDropdown, 200); });
        }
        document.addEventListener('click', function(e) {
            if (dropdown && e.target !== input && !dropdown.contains(e.target)) hideDropdown();
        });
    })();
</script>
<script>

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
