@extends('layouts.app')

@section('content')
<style>
    .location-item {
        transition: all 0.2s ease;
    }
    .location-item:hover {
        background-color: #f8f9fa !important;
        border-left-color: #C6A87D !important;
        transform: translateX(2px);
    }
    .location-item.active {
        background-color: #e7f3ff !important;
        border-left-color: #1F2A44 !important;
    }
    #locationList {
        border: 1px solid #dee2e6;
    }
    #locationList .list-group-item {
        border: none;
        border-bottom: 1px solid #f0f0f0;
    }
    #locationList .list-group-item:last-child {
        border-bottom: none;
    }
</style>

<div class="container-fluid">
    <div class="page-header">
        <h2><i class="bi bi-geo-alt me-2"></i>Location Asset Lookup</h2>
       
    </div>

    {{-- Error/Warning Messages --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- SEARCH BOX --}}
    <div class="form-group position-relative mb-4">
        <label for="location_name" class="form-label fw-semibold">Search Location</label>
        <input type="text" id="location_name" name="location_name" 
               class="form-control form-control-lg" 
               placeholder="Type location name" 
               autocomplete="off">
        <div id="locationList"
             class="list-group position-absolute w-100 mt-1"
             style="z-index: 1000; max-height: 300px; overflow-y: auto; display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px;">
        </div>
    </div>
    {{-- TABLE --}}
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
                            <th>Purchase Date</th>
                            <th>Expiry Date</th>
                        </tr>
                    </thead>
                    <tbody id="assetTableBody">
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Type a location to view its assets.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){

    // 🔍 Autocomplete locations - starts from first letter
    let searchTimeout;
    $('#location_name').on('input', function(){
        let query = $(this).val().trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        if(query.length >= 1){
            // Small delay to avoid too many requests while typing
            searchTimeout = setTimeout(function(){
                $.ajax({
                    url: "/locations/autocomplete",
                    type: "GET",
                    data: { query: query },
                    success: function(data){
                        let locationList = $('#locationList');
                        locationList.empty();
                        
                        if (data.length === 0) {
                            locationList.append('<div class="list-group-item text-muted text-center">No locations found</div>');
                        } else {
                            data.forEach(function(loc){
                                let displayName = loc.location_name || 'N/A';
                                let highlight = query.length > 0 ? displayName.replace(new RegExp(query, 'gi'), '<strong>$&</strong>') : displayName;
                                let sub = [loc.location_country, loc.location_entity].filter(Boolean).join(' • ');
                                locationList.append(`
                                    <a href="#" class="list-group-item list-group-item-action location-item" 
                                       data-id="${loc.id}" 
                                       data-name="${displayName}"
                                       style="cursor: pointer; border-left: 3px solid #1F2A44;">
                                        <div class="fw-semibold">${highlight}</div>
                                        ${sub ? '<small class="text-muted">' + sub + '</small>' : ''}
                                    </a>
                                `);
                            });
                        }
                        locationList.fadeIn(200);
                    },
                    error: function(){
                        $('#locationList').html('<div class="list-group-item text-danger text-center">Error loading locations</div>').fadeIn();
                    }
                });
            }, 200); // 200ms delay
        } else {
            $('#locationList').fadeOut(200);
        }
    });

    // Store current location ID
    let currentLocationId = null;

    //  Selecting from Dropdown
    $(document).on('click', '.location-item', function(e){
        e.preventDefault();
        let locationId = $(this).data('id');
        let locationName = $(this).data('name');

        $('#location_name').val(locationName);
        $('#locationList').fadeOut(200);
        currentLocationId = locationId;

      
        $.ajax({
            url: `/locations/${locationId}/assets`,
            type: "GET",
            success: function(data){
                let tableBody = $('#assetTableBody');
                tableBody.empty();

                if (data.length > 0) {
                    data.forEach((asset, index) => {
                        tableBody.append(`
                            <tr>
                                <td>${index + 1}</td>
                                <td>${asset.asset_id || 'N/A'}</td>
                                <td>${asset.category || 'N/A'}</td>
                                <td>${asset.brand || 'N/A'}</td>
                                <td>${asset.serial_number || 'N/A'}</td>
                                <td>${asset.po_number || 'N/A'}</td>
                                <td>${asset.purchase_date || 'N/A'}</td>
                                <td>${asset.expiry_date || 'N/A'}</td>
                            </tr>
                        `);
                    });
                    // Show download button
                    $('#downloadDropdown').show();
                } else {
                    tableBody.append(`
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No assets assigned to this location.
                            </td>
                        </tr>
                    `);
                    $('#downloadDropdown').hide();
                }
            },
            error: function(){
                $('#downloadDropdown').hide();
            }
        });
    });

    // Download handlers
    $('#downloadPdf').on('click', function(e){
        e.preventDefault();
        if (currentLocationId) {
            window.location.href = `/locations/${currentLocationId}/assets/export?format=pdf`;
        }
    });

    $('#downloadCsv').on('click', function(e){
        e.preventDefault();
        if (currentLocationId) {
            window.location.href = `/locations/${currentLocationId}/assets/export?format=csv`;
        }
    });
    // Hide suggestions when clicking outside
    $(document).click(function(e){
        if(!$(e.target).closest('#location_name, #locationList').length){
            $('#locationList').fadeOut(200);
        }
    });
    
    // Keyboard navigation (Arrow keys and Enter)
    let selectedIndex = -1;
    $('#location_name').on('keydown', function(e){
        let items = $('.location-item');
        
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
            $('#locationList').fadeOut(200);
            selectedIndex = -1;
        }
    });

});
</script>
@endsection
