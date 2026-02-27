@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="bi bi-funnel me-2"></i>Filter Assets</h2>
      
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
        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filter Assets</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label for="filter_category" class="form-label">Asset Category</label>
                <select id="filter_category" class="form-control">
                    <option value="">-- All Categories --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 position-relative">
                <label for="filter_serial" class="form-label">Serial Number</label>
                <input type="text" id="filter_serial" class="form-control" placeholder="Type to search serial numbers..." autocomplete="off">
                <div id="serial_suggestions" class="list-group position-absolute w-100 mt-1" style="z-index: 1000; max-height: 200px; overflow-y: auto; display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 6px;"></div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" id="btnSearch" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Search
                </button>
                <button type="button" id="btnClear" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </button>
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
                                <th>Vendor Name</th>
                                <th>Value</th>
                                <th>Serial Number</th>
                                <th>Features</th>
                                <th>Invoice</th>
                                <th>History</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="assetsTableBody">
                            <tr>
                                <td colspan="14" class="text-center text-muted py-4">Select a category or type a serial number, then click Search.</td>
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
    // Store current filter params for download
    let currentFilterParams = { category_id: null, serial_number: null };
    let serialSearchTimeout;

    // Serial number autocomplete - type to get matching options
    $('#filter_serial').on('input', function() {
        const q = $(this).val().trim();
        clearTimeout(serialSearchTimeout);
        if (q.length < 1) {
            $('#serial_suggestions').hide().empty();
            return;
        }
        serialSearchTimeout = setTimeout(function() {
            const categoryId = $('#filter_category').val();
            const params = new URLSearchParams({ q: q });
            if (categoryId) params.append('category_id', categoryId);
            $.get('/api/assets/serial-numbers?' + params.toString(), function(serials) {
                const $list = $('#serial_suggestions');
                $list.empty();
                if (serials && serials.length > 0) {
                    serials.forEach(function(s) {
                        const escaped = $('<div>').text(s).html();
                        $list.append(`<a href="#" class="list-group-item list-group-item-action serial-item">${escaped}</a>`);
                    });
                    $list.show();
                } else {
                    $list.hide();
                }
            });
        }, 250);
    });

    $(document).on('click', '.serial-item', function(e) {
        e.preventDefault();
        $('#filter_serial').val($(this).text());
        $('#serial_suggestions').hide().empty();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#filter_serial, #serial_suggestions').length) {
            $('#serial_suggestions').hide();
        }
    });

    function loadFilteredAssets() {
        const categoryId = $('#filter_category').val();
        const serialNumber = $('#filter_serial').val().trim();

        if (!categoryId && !serialNumber) {
            $('#assets-section').hide();
            $('#downloadDropdown').hide();
            return;
        }

        currentFilterParams = { category_id: categoryId || null, serial_number: serialNumber || null };
        const params = new URLSearchParams();
        if (categoryId) params.append('category_id', categoryId);
        if (serialNumber) params.append('serial_number', serialNumber);

        $.get(`/api/assets/filter?${params.toString()}`, function(assets) {
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
                    if (asset.invoice_url) {
                        invoiceHtml = `<a href="${asset.invoice_url}" target="_blank" class="btn btn-sm btn-outline-primary">
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

                    // Delete button
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const deleteUrl = '{{ url("assets") }}/' + asset.id;
                    let deleteHtml = '';
                    if (asset.id) {
                        deleteHtml = `<form action="${deleteUrl}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this asset? This will also delete all related transactions.');">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>`;
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
                            <td>${asset.vendor_name || '-'}</td>
                            <td>${asset.value || '-'}</td>
                            <td>${asset.serial_number || 'N/A'}</td>
                            <td>${featuresHtml}</td>
                            <td>${invoiceHtml}</td>
                            <td>${historyHtml}</td>
                            <td>${deleteHtml}</td>
                        </tr>
                    `);
                });
                $('#assets-section').show();
                $('#downloadDropdown').show();
            } else {
                tableBody.append('<tr><td colspan="14" class="text-center text-muted py-4">No assets found in this category.</td></tr>');
                $('#assets-section').show();
                $('#downloadDropdown').hide();
            }
        }).fail(function(xhr, status, error) {
            console.error('API Error:', status, error, xhr.responseText);
            let errorMsg = 'Error loading assets. Please try again.';
            try {
                const resp = JSON.parse(xhr.responseText);
                if (resp.message) {
                    errorMsg = 'Error: ' + resp.message;
                }
            } catch(e) {}
            $('#assetsTableBody').html('<tr><td colspan="14" class="text-center text-danger py-4">' + errorMsg + '</td></tr>');
            $('#assets-section').show();
            $('#downloadDropdown').hide();
        });
    }

    // Search on button click
    $('#btnSearch').on('click', function() {
        $('#serial_suggestions').hide();
        loadFilteredAssets();
    });

    // Search on Enter
    $('#filter_serial').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#serial_suggestions').hide();
            loadFilteredAssets();
        }
    });

    // When category changes, search
    $('#filter_category').on('change', function() {
        loadFilteredAssets();
    });

    // Clear filters
    $('#btnClear').on('click', function() {
        $('#filter_category').val('');
        $('#filter_serial').val('');
        $('#serial_suggestions').hide().empty();
        $('#assets-section').hide();
        $('#downloadDropdown').hide();
    });

    // Download handlers
    $('#downloadPdf').on('click', function(e){
        e.preventDefault();
        if (currentFilterParams.category_id || currentFilterParams.serial_number) {
            const params = new URLSearchParams({ format: 'pdf' });
            if (currentFilterParams.category_id) params.append('category_id', currentFilterParams.category_id);
            if (currentFilterParams.serial_number) params.append('serial_number', currentFilterParams.serial_number);
            window.location.href = `/assets/filter/export?${params.toString()}`;
        }
    });

    $('#downloadCsv').on('click', function(e){
        e.preventDefault();
        if (currentFilterParams.category_id || currentFilterParams.serial_number) {
            const params = new URLSearchParams({ format: 'csv' });
            if (currentFilterParams.category_id) params.append('category_id', currentFilterParams.category_id);
            if (currentFilterParams.serial_number) params.append('serial_number', currentFilterParams.serial_number);
            window.location.href = `/assets/filter/export?${params.toString()}`;
        }
    });
</script>
@endsection
