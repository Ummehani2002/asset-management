@extends('layouts.app')
@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-tags me-2"></i>Manage Categories</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- View category: dropdown â€” select one to see that category only --}}
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>View category</h5>
        <div class="row align-items-end">
            <div class="col-md-5">
                <label for="view_category_id" class="form-label">Select category</label>
                <select id="view_category_id" class="form-control" onchange="window.location.href=this.options[this.selectedIndex].value">
                    <option value="{{ route('categories.manage', request()->only('set_values')) }}" {{ empty($selectedCategoryId) ? 'selected' : '' }}>â€” All â€”</option>
                    @foreach($categories as $cat)
                        <option value="{{ route('categories.manage', array_merge(request()->only('set_values'), ['category_id' => $cat->id])) }}" {{ (isset($selectedCategoryId) && $selectedCategoryId == $cat->id) ? 'selected' : '' }}>
                            {{ $cat->category_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if(isset($selectedCategoryId) && $selectedCategoryId)
                <div class="col-md-7">
                    <p class="text-muted small mb-0">Showing only <strong>{{ $categories->firstWhere('id', $selectedCategoryId)->category_name ?? 'selected' }}</strong> â€” brands and models for this category.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Add New Category Form --}}
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-plus-circle me-2"></i>Add New Category</h5>
        <form action="{{ route('categories.store') }}" method="POST" autocomplete="off">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <input type="text" name="category_name" class="form-control" placeholder="Category Name" required>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Add Category
                    </button>
                    <button type="button" class="btn btn-secondary ms-2" onclick="resetForm(this)">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>

    @foreach($categoriesToShow as $category)
        <div class="master-table-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="color: white; margin: 0;">
                    <i class="bi bi-folder me-2"></i>{{ $category->category_name }}
                </h5>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadDropdown{{ $category->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i> Download
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadDropdown{{ $category->id }}">
                            <li><a class="dropdown-item" href="{{ route('categories.export', ['id' => $category->id, 'format' => 'pdf']) }}">
                                <i class="bi bi-file-pdf me-2"></i>PDF
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('categories.export', ['id' => $category->id, 'format' => 'csv']) }}">
                                <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
                            </a></li>
                        </ul>
                    </div>
                    <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete category {{ addslashes($category->category_name) }} and all its brands and features?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                {{-- Add New Brand: category search (dropdown) + brand name --}}
                <div class="mb-4">
                    <h6 class="mb-2">Add New Brand</h6>
                    <form action="{{ route('brands.store') }}" method="POST" class="row g-2 align-items-end" autocomplete="off">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label small">Category</label>
                            <select name="asset_category_id" class="form-control form-control-sm" required>
                                <option value="">-- Select Category --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ (isset($selectedCategoryId) && $selectedCategoryId == $cat->id) ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Brand Name</label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Lenovo" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-plus-circle me-1"></i>Add Brand
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm ms-1" onclick="resetForm(this)">Cancel</button>
                        </div>
                    </form>
                </div>

                {{-- Select brand: only one brand displayed at a time --}}
                @php
                    $categoryBrands = $category->brands;
                    $baseParams = array_merge(request()->only(['category_id', 'set_values']), ['category_id' => $category->id]);
                @endphp
                <div class="mb-3">
                    <label class="form-label">Select brand</label>
                    <select class="form-control select-brand-dropdown" style="max-width: 320px;" data-base-url="{{ route('categories.manage', array_filter(array_merge(request()->only(['category_id', 'set_values']), ['category_id' => $category->id]))) }}">
                        <option value="">-- Select brand --</option>
                        @foreach($categoryBrands as $b)
                            <option value="{{ $b->id }}" {{ (isset($selectedBrandId) && $selectedBrandId == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($categoryBrands->isEmpty())
                    <p class="text-muted">No brands yet. Add a brand above (select category and enter brand name).</p>
                @else
                    {{-- Show only the selected brand (one at a time) --}}
                    @php $brandsToShow = isset($selectedBrandId) ? $categoryBrands->where('id', $selectedBrandId) : collect(); @endphp
                    @forelse($brandsToShow as $brand)
                <div class="border rounded p-3 mb-3" id="brand-block-{{ $brand->id }}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>{{ $brand->name }}</strong>
                        <div class="d-flex gap-1">
                            <a href="{{ route('brands.edit', $brand->id) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i> Edit</a>
                            <form action="{{ route('brands.destroy', $brand->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete brand {{ addslashes($brand->name) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-7">
                                {{-- Add Model --}}
                                <div class="mb-2">
                                    <form action="{{ route('brand-models.store') }}" method="POST" class="d-inline-flex align-items-center gap-2" autocomplete="off">
                                        @csrf
                                        <input type="hidden" name="brand_id" value="{{ $brand->id }}">
                                        <input type="text" name="model_number" class="form-control form-control-sm" style="max-width: 180px;" placeholder="Model number" required>
                                        <button type="submit" class="btn btn-sm btn-info"><i class="bi bi-plus-circle"></i> Add Model</button>
                                    </form>
                                </div>
                                @if(isset($brand->models) && $brand->models->count() > 0)
                                    <div class="mb-2 small">
                                        @foreach($brand->models as $bModel)
                                            <span class="badge bg-secondary me-1 mb-1">
                                                {{ $bModel->model_number }}
                                                @php $setValuesParams = ['set_values' => $bModel->id, 'category_id' => $category->id, 'brand_id' => $brand->id]; @endphp
                                                <a href="{{ route('categories.manage', $setValuesParams) }}#brand-{{ $brand->id }}" class="text-white ms-1" title="Set values"><i class="bi bi-pencil-square me-1"></i>Set values</a>
                                                <form action="{{ route('brand-models.destroy', $bModel->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this model?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link p-0 ms-1 text-white" style="font-size: 0.7rem;"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            {{-- Features table for this brand --}}
                            @if($brand->features->count())
                                <table class="table table-sm table-bordered mb-2">
                                    <thead class="table-light">
                                        <tr><th>Feature Name</th><th>Sub Fields</th><th>Actions</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($brand->features as $feature)
                                            <tr>
                                                <td>{{ $feature->feature_name }}</td>
                                                <td>{{ $feature->sub_fields && count($feature->sub_fields) > 0 ? implode(', ', $feature->sub_fields) : 'â€”' }}</td>
                                                <td>
                                                    <a href="{{ route('features.edit', $feature->id) }}" class="btn btn-xs btn-outline-warning" style="font-size: 0.7rem;">Edit</a>
                                                    <form action="{{ route('features.destroy', $feature->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this feature?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-outline-danger" style="font-size: 0.7rem;">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                            <form action="{{ route('features.store') }}" method="POST" class="d-flex gap-2" autocomplete="off">
                                @csrf
                                <input type="hidden" name="brand_id" value="{{ $brand->id }}">
                                <input type="text" name="feature_name" class="form-control form-control-sm" style="max-width: 200px;" placeholder="Add feature" required>
                                <button type="submit" class="btn btn-primary btn-sm">Add feature</button>
                            </form>
                        </div>
                        <div class="col-md-5">
                                {{-- Set values: two-column for Laptop, else one-at-a-time --}}
                                @if(isset($setValuesModel) && $setValuesModel->brand_id == $brand->id)
                                    @php
                                        $isLaptopCategory = $category->category_name && strtolower(trim($category->category_name)) === 'laptop';
                                    @endphp
                                    <div id="brand-{{ $brand->id }}" class="set-values-one-at-a-time">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Set values for model &ldquo;{{ $setValuesModel->model_number }}&rdquo;</strong>
                                            @php $closeParams = array_filter(array_merge(request()->only('category_id'), ['category_id' => $selectedCategoryId ?? null, 'brand_id' => $selectedBrandId ?? null])); @endphp
                                            <a href="{{ route('categories.manage', $closeParams) }}" class="btn btn-sm btn-outline-secondary">Close</a>
                                        </div>
                                        <form action="{{ route('brand-models.update-feature-values', $setValuesModel->id) }}" method="POST" autocomplete="off">
                                            @csrf
                                            @if(isset($selectedCategoryId) && $selectedCategoryId)
                                                <input type="hidden" name="category_id" value="{{ $selectedCategoryId }}">
                                            @endif
                                            @if($isLaptopCategory)
                                                {{-- Laptop: two-column layout (Field | Value) --}}
                                                <table class="table table-bordered">
                                                    <thead class="table-light"><tr><th>Field</th><th>Value</th></tr></thead>
                                                    <tbody>
                                                        @foreach($setValuesFeatures as $feature)
                                                            @php
                                                                $fv = $setValuesByFeature->get($feature->id);
                                                                $val = $fv ? $fv->feature_value : null;
                                                                $subVals = $fv && $feature->sub_fields ? @json_decode($fv->feature_value, true) : [];
                                                                $subVals = is_array($subVals) ? $subVals : [];
                                                                $isModelNumber = strtolower($feature->feature_name ?? '') === 'model number';
                                                            @endphp
                                                            <tr>
                                                                <td class="fw-semibold">{{ $feature->feature_name }}</td>
                                                                <td>
                                                                    @if($feature->sub_fields && count($feature->sub_fields) > 0)
                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            @foreach($feature->sub_fields as $subField)
                                                                                <input type="text" name="features_{{ $feature->id }}_{{ $subField }}" class="form-control form-control-sm" style="width: 120px;" value="{{ $subVals[$subField] ?? '' }}" placeholder="{{ $subField }}">
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <input type="text" name="features_{{ $feature->id }}" class="form-control form-control-sm" value="{{ $isModelNumber ? ($setValuesModel->model_number ?? '') : ($val ?? '') }}" {{ $isModelNumber ? 'readonly' : '' }} placeholder="{{ $feature->feature_name }}">
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                {{-- Non-laptop: one feature at a time (Previous/Next) --}}
                                                <div class="set-values-steps border rounded bg-white p-3" style="min-height: 100px;">
                                                    @foreach($setValuesFeatures as $idx => $feature)
                                                        @php
                                                            $fv = $setValuesByFeature->get($feature->id);
                                                            $val = $fv ? $fv->feature_value : null;
                                                            $subVals = $fv && $feature->sub_fields ? @json_decode($fv->feature_value, true) : [];
                                                            $subVals = is_array($subVals) ? $subVals : [];
                                                            $isModelNumber = strtolower($feature->feature_name ?? '') === 'model number';
                                                        @endphp
                                                        <div class="set-values-step {{ $idx === 0 ? '' : 'd-none' }}" data-step="{{ $idx }}">
                                                            <div class="mb-2"><strong>{{ $feature->feature_name }}</strong></div>
                                                            @if($feature->sub_fields && count($feature->sub_fields) > 0)
                                                                <div class="d-flex flex-wrap gap-2">
                                                                    @foreach($feature->sub_fields as $subField)
                                                                        <input type="text" name="features_{{ $feature->id }}_{{ $subField }}" class="form-control form-control-sm" style="width: 120px;" value="{{ $subVals[$subField] ?? '' }}" placeholder="{{ $subField }}">
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <input type="text" name="features_{{ $feature->id }}" class="form-control form-control-sm" value="{{ $isModelNumber ? ($setValuesModel->model_number ?? '') : ($val ?? '') }}" {{ $isModelNumber ? 'readonly' : '' }} placeholder="{{ $feature->feature_name }}">
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    @php $totalFeatures = $setValuesFeatures->count(); @endphp
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary set-values-prev" disabled>Previous</button>
                                                        <span class="mx-2 set-values-counter text-muted small">1 of {{ $totalFeatures }}</span>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary set-values-next">{{ $totalFeatures > 1 ? 'Next' : '' }}</button>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="mt-2">
                                                <button type="submit" class="btn btn-primary btn-sm">Save feature values</button>
                                            </div>
                                        </form>
                                        @if(!$isLaptopCategory)
                                            <script>
                                            (function() {
                                                var container = document.querySelector('#brand-{{ $brand->id }} .set-values-one-at-a-time');
                                                if (!container) return;
                                                var steps = container.querySelectorAll('.set-values-step');
                                                var total = steps.length;
                                                var prevBtn = container.querySelector('.set-values-prev');
                                                var nextBtn = container.querySelector('.set-values-next');
                                                var counterEl = container.querySelector('.set-values-counter');
                                                var current = 0;
                                                function showStep(i) {
                                                    current = i;
                                                    steps.forEach(function(s, idx) { s.classList.toggle('d-none', idx !== current); });
                                                    if (prevBtn) prevBtn.disabled = current === 0;
                                                    if (nextBtn) { nextBtn.disabled = current === total - 1; nextBtn.textContent = current === total - 1 ? '' : 'Next'; }
                                                    if (counterEl) counterEl.textContent = (current + 1) + ' of ' + total;
                                                }
                                                if (prevBtn) prevBtn.addEventListener('click', function() { if (current > 0) showStep(current - 1); });
                                                if (nextBtn) nextBtn.addEventListener('click', function() { if (current < total - 1) showStep(current + 1); });
                                            })();
                                            </script>
                                        @endif
                                    </div>
                                @endif
                        </div>
                    </div>
                </div>
                    @empty
                                <p class="text-muted">Select a brand from the dropdown above to view or edit it.</p>
                            @endforelse
                @endif
            </div>
        </div>
    @endforeach
</div>
<script>
document.querySelectorAll('.select-brand-dropdown').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var base = this.getAttribute('data-base-url');
        var brandId = this.value;
        var url = base + (base.indexOf('?') >= 0 ? '&' : '?') + (brandId ? 'brand_id=' + brandId : '');
        if (url !== (window.location.pathname + window.location.search)) window.location.href = url;
    });
});
</script>
@endsection
