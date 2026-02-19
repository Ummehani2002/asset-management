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
                @php
                    $categoryBrands = $category->brands;
                    $brandsToShow = isset($selectedBrandId) ? $categoryBrands->where('id', $selectedBrandId) : collect();
                    $baseManageUrl = route('categories.manage', array_filter(array_merge(request()->only(['category_id', 'set_values']), ['category_id' => $category->id])));
                @endphp
                <div class="row g-4">
                    {{-- LEFT: Selection only --}}
                    <div class="col-lg-4 col-md-5">
                        <div class="border rounded p-3 bg-light">
                            <h6 class="mb-3"><i class="bi bi-funnel me-2"></i>Select</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Brand</label>
                                <select class="form-control form-control-sm select-brand-dropdown" data-base-url="{{ $baseManageUrl }}">
                                    <option value="">-- Select brand --</option>
                                    @foreach($categoryBrands as $b)
                                        <option value="{{ $b->id }}" {{ (isset($selectedBrandId) && $selectedBrandId == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if($brandsToShow->isNotEmpty())
                                @php $selBrand = $brandsToShow->first(); @endphp
                                <div>
                                    <label class="form-label small fw-semibold">Model (set values)</label>
                                    @if($selBrand->models && $selBrand->models->count() > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($selBrand->models as $bModel)
                                                @php $setValuesParams = ['set_values' => $bModel->id, 'category_id' => $category->id, 'brand_id' => $selBrand->id]; @endphp
                                                <a href="{{ route('categories.manage', $setValuesParams) }}#content-panel" class="badge {{ (isset($setValuesModel) && $setValuesModel->id == $bModel->id) ? 'bg-primary' : 'bg-secondary' }} text-decoration-none">{{ $bModel->model_number }}</a>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">Click model to set values</small>
                                    @else
                                        <p class="text-muted small mb-0">No models. Add one on the right.</p>
                                    @endif
                                </div>
                            @else
                                <p class="text-muted small mb-0">Select a brand first.</p>
                            @endif
                        </div>
                    </div>
                    {{-- RIGHT: Add brand, model, features and table --}}
                    <div class="col-lg-8 col-md-7" id="content-panel">
                @if($categoryBrands->isEmpty())
                    <div class="border rounded p-4">
                        <h6 class="mb-3">Add brand name</h6>
                        <form action="{{ route('brands.store') }}" method="POST" autocomplete="off">
                            @csrf
                            <input type="hidden" name="asset_category_id" value="{{ $category->id }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-6">
                                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Lenovo" required>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Brand</button>
                                </div>
                            </div>
                        </form>
                        <p class="text-muted small mt-3 mb-0">No brands yet. Add one above, then select it on the left.</p>
                    </div>
                @else
                    @forelse($brandsToShow as $brand)
                <div id="brand-{{ $brand->id }}">
                    <div class="border rounded p-3 mb-3">
                        <h6 class="mb-2">Add brand name</h6>
                        <form action="{{ route('brands.store') }}" method="POST" class="row g-2 align-items-end" autocomplete="off">
                            @csrf
                            <input type="hidden" name="asset_category_id" value="{{ $category->id }}">
                            <div class="col-auto"><input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Lenovo" style="min-width: 160px;" required></div>
                            <div class="col-auto"><button type="submit" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Brand</button></div>
                        </form>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h6 class="mb-0">{{ $brand->name }}</h6>
                        <div class="d-flex gap-1">
                            <a href="{{ route('brands.edit', $brand->id) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i> Edit</a>
                            <form action="{{ route('brands.destroy', $brand->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete brand {{ addslashes($brand->name) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                    <div class="border rounded p-3 mb-3">
                        <h6 class="mb-2">Add model number</h6>
                        <form action="{{ route('brand-models.store') }}" method="POST" class="d-inline-flex align-items-center gap-2" autocomplete="off">
                            @csrf
                            <input type="hidden" name="brand_id" value="{{ $brand->id }}">
                            <input type="text" name="model_number" class="form-control form-control-sm" placeholder="Model number" style="max-width: 160px;" required>
                            <button type="submit" class="btn btn-info btn-sm"><i class="bi bi-plus-circle"></i> Add Model</button>
                        </form>
                    </div>
                            @php
                                $isSetValuesMode = isset($setValuesModel) && $setValuesModel->brand_id == $brand->id;
                                $tableFeatures = $isSetValuesMode ? $setValuesFeatures : $brand->features;
                                $tableSetValuesByFeature = $isSetValuesMode ? $setValuesByFeature : collect([]);
                            @endphp
                    <div class="border rounded p-3 mb-3">
                        <h6 class="mb-2">Features</h6>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            @forelse($tableFeatures as $feature)
                                <div class="d-inline-flex align-items-center gap-1 border rounded px-2 py-1 bg-light">
                                    <span class="fw-semibold">{{ $feature->feature_name }}</span>
                                    <a href="{{ route('features.edit', $feature->id) }}" class="btn btn-sm btn-outline-warning py-0 px-1">Edit</a>
                                    <form action="{{ route('features.destroy', $feature->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this feature?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">Delete</button>
                                    </form>
                                </div>
                            @empty
                                <span class="text-muted small">No features yet. Add a feature below.</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="border rounded p-3 mb-3">
                        <h6 class="mb-2">Add feature</h6>
                        <form action="{{ route('features.store') }}" method="POST" class="d-flex gap-2 align-items-center" autocomplete="off">
                            @csrf
                            <input type="hidden" name="brand_id" value="{{ $brand->id }}">
                            <input type="text" name="feature_name" class="form-control form-control-sm" style="max-width: 200px;" placeholder="Feature name" required>
                            <button type="submit" class="btn btn-primary btn-sm">Add feature</button>
                        </form>
                    </div>
                </div>
                    @empty
                        <p class="text-muted">Select a brand on the left to view or edit it.</p>
                    @endforelse
                @endif
                    </div>
                </div>
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
