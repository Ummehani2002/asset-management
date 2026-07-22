@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h2 class="mb-1"><i class="bi bi-pencil-square me-2"></i>Edit Asset</h2>
            <p class="mb-0 text-muted">Update serial number, brand, model, PO number, and commercial details for this asset.</p>
        </div>
        <a href="{{ route('assets.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Asset Master
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="master-form-card">
        <form action="{{ route('assets.update', $asset->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Asset ID</label>
                    <input type="text" class="form-control" value="{{ $asset->asset_id }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <input type="text" class="form-control" value="{{ $asset->category->category_name ?? 'N/A' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Serial Number <span class="text-danger">*</span></label>
                    <input type="text" name="serial_number" class="form-control @error('serial_number') is-invalid @enderror" value="{{ old('serial_number', $asset->serial_number) }}" placeholder="Enter serial number" autocomplete="off" required>
                    @error('serial_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Brand <span class="text-danger">*</span></label>
                    <select name="brand_id" id="brand" class="form-control @error('brand_id') is-invalid @enderror" required>
                        <option value="">-- Select Brand --</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" {{ (string) old('brand_id', $asset->brand_id) === (string) $brand->id ? 'selected' : '' }}>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('brand_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Model</label>
                    <select name="brand_model_id" id="brand_model" class="form-control @error('brand_model_id') is-invalid @enderror">
                        <option value="">-- Select Model --</option>
                        @foreach($models as $model)
                            <option
                                value="{{ $model->id }}"
                                data-brand-id="{{ $model->brand_id }}"
                                {{ (string) old('brand_model_id', $selectedBrandModelId) === (string) $model->id ? 'selected' : '' }}
                            >
                                {{ ($model->brand->name ?? '') . ' - ' . ($model->model_number ?? '') }}
                            </option>
                        @endforeach
                    </select>
                    @error('brand_model_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if(!$selectedBrandModelId && !empty($asset->model_number))
                        <div class="form-text">Current model on asset: <strong>{{ $asset->model_number }}</strong> (not found in Brand & Model master — pick a model to update it)</div>
                    @endif
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">PO Number</label>
                    <input type="text" name="po_number" class="form-control" value="{{ old('po_number', $asset->po_number) }}" placeholder="Enter PO number">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vendor Name</label>
                    <input type="text" name="vendor_name" class="form-control" value="{{ old('vendor_name', $asset->vendor_name) }}" placeholder="Enter vendor name">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Value</label>
                    <input type="number" step="0.01" name="value" class="form-control" value="{{ old('value', $asset->value) }}" placeholder="Enter value">
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Save Changes
                </button>
                <a href="{{ route('assets.index') }}" class="btn btn-secondary ms-2">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
(function () {
    var allModels = @json($modelsJson);
    var selectedModelId = @json(old('brand_model_id', $selectedBrandModelId));
    var settingBrandFromModel = false;

    function fillModels(brandId, keepSelected) {
        var $model = $('#brand_model');
        var current = keepSelected ? (keepSelected === true ? $model.val() : keepSelected) : '';
        $model.html('<option value="">-- Select Model --</option>');
        allModels.forEach(function (m) {
            if (brandId && String(m.brand_id) !== String(brandId)) {
                return;
            }
            var label = (m.brand_name || '') + ' - ' + (m.model_number || '');
            var $opt = $('<option></option>').attr('value', m.id).attr('data-brand-id', m.brand_id).text(label);
            if (current && String(current) === String(m.id)) {
                $opt.prop('selected', true);
            }
            $model.append($opt);
        });
    }

    $('#brand').on('change', function () {
        if (settingBrandFromModel) {
            return;
        }
        fillModels($(this).val(), false);
    });

    $('#brand_model').on('change', function () {
        var brandId = $(this).find('option:selected').data('brand-id');
        if (!brandId) {
            return;
        }
        settingBrandFromModel = true;
        $('#brand').val(String(brandId));
        settingBrandFromModel = false;
    });

    var initialBrand = $('#brand').val();
    if (initialBrand) {
        fillModels(initialBrand, selectedModelId || true);
    }
})();
</script>
@endsection
