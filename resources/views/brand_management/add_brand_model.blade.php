@extends('layouts.app')
@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-tags me-2"></i>Add Brand & Model</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Add New Category --}}
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-plus-circle me-2"></i>Add New Category</h5>
        <form action="{{ route('categories.store') }}" method="POST" class="row g-3 align-items-end" autocomplete="off">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Category Name</label>
                <input type="text" name="category_name" class="form-control" placeholder="e.g. Laptop, Printer" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-circle me-1"></i>Add Category</button>
            </div>
        </form>
    </div>

    {{-- Step 1: Select category — when selected, show details below --}}
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Select category</h5>
        <div class="row align-items-end">
            <div class="col-md-5">
                <label for="view_category_id" class="form-label">Category</label>
                <select id="view_category_id" class="form-select" onchange="window.location.href=this.options[this.selectedIndex].value">
                    <option value="{{ route('brand-management.add-brand-model') }}">— Select category —</option>
                    @foreach($categories as $cat)
                        <option value="{{ route('brand-management.add-brand-model', ['category_id' => $cat->id]) }}" {{ $selectedCategoryId == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                    @endforeach
                </select>
            </div>
            @if($selectedCategoryId)
                <div class="col-md-7">
                    <p class="text-muted small mb-0">Adding brands, models and features for <strong>{{ $categories->firstWhere('id', $selectedCategoryId)->category_name ?? 'this category' }}</strong>.</p>
                </div>
            @endif
        </div>
    </div>

    @if($selectedCategoryId)
        @php $category = $categories->firstWhere('id', $selectedCategoryId); @endphp
        <div class="master-form-card mb-4">
            <h5 class="mb-3"><i class="bi bi-plus-circle me-2"></i>Add brand to this category</h5>
            <form action="{{ route('brands.store') }}" method="POST" class="d-flex flex-wrap gap-2 align-items-end" autocomplete="off">
                @csrf
                <input type="hidden" name="asset_category_id" value="{{ $selectedCategoryId }}">
                <div>
                    <label class="form-label small">Brand name</label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Lenovo" style="min-width: 180px;" required>
                </div>
                <div>
                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Brand</button>
                </div>
            </form>
        </div>

        <div class="master-form-card mb-4">
            <h5 class="mb-3"><i class="bi bi-cpu me-2"></i>Add model & features</h5>
            @if($brands->isEmpty())
                <p class="text-muted mb-0">No brands yet. Add a brand above first.</p>
            @else
                {{-- Single Brand Selector --}}
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Select Brand</label>
                        <select id="selected_brand" class="form-select" required>
                            <option value="">-- Select Brand --</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                {{-- Model and Feature inputs in a row --}}
                <div class="row g-3" id="model_feature_section" style="display: none;">
                    {{-- Add Model --}}
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <label class="form-label fw-bold"><i class="bi bi-collection me-1"></i>Add Model</label>
                            <form action="{{ route('brand-models.store') }}" method="POST" class="d-flex gap-2 align-items-end" autocomplete="off">
                                @csrf
                                <input type="hidden" name="brand_id" id="model_brand_id">
                                <div class="flex-grow-1">
                                    <input type="text" name="model_number" class="form-control form-control-sm" placeholder="Model number" required>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-info btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Model</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    {{-- Add Feature --}}
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <label class="form-label fw-bold"><i class="bi bi-gear me-1"></i>Add Feature</label>
                            <form action="{{ route('features.store') }}" method="POST" class="d-flex gap-2 align-items-end" autocomplete="off">
                                @csrf
                                <input type="hidden" name="brand_id" id="feature_brand_id">
                                <div class="flex-grow-1">
                                    <input type="text" name="feature_name" class="form-control form-control-sm" placeholder="e.g. RAM, Processor" required>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Feature</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <script>
                    document.getElementById('selected_brand').addEventListener('change', function() {
                        var brandId = this.value;
                        var section = document.getElementById('model_feature_section');
                        if (brandId) {
                            document.getElementById('model_brand_id').value = brandId;
                            document.getElementById('feature_brand_id').value = brandId;
                            section.style.display = 'flex';
                        } else {
                            section.style.display = 'none';
                        }
                    });
                </script>
            @endif
        </div>

        {{-- List of brands, models and features for this category --}}
        <div class="master-table-card">
            <div class="card-header">
                <h5 class="mb-0" style="color: white;"><i class="bi bi-list-ul me-2"></i>{{ optional($category)->category_name ?? 'Category' }} — Brands, models & features</h5>
            </div>
            <div class="card-body">
                @forelse($brands as $brand)
                    <div class="border rounded p-3 mb-3">
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
                        <div class="small">
                            <strong>Category:</strong> <span class="badge bg-info">{{ optional($category)->category_name ?? 'N/A' }}</span>
                        </div>
                        <div class="small mt-1">
                            <strong>Models:</strong>
                            @if($brand->models->isEmpty())
                                <span class="text-muted">None</span>
                            @else
                                @foreach($brand->models as $m)
                                    <span class="badge bg-secondary me-1">{{ $m->model_number }}</span>
                                @endforeach
                            @endif
                        </div>
                        <div class="small mt-1">
                            <strong>Features:</strong>
                            @if($brand->features->isEmpty())
                                <span class="text-muted">None</span>
                            @else
                                @foreach($brand->features as $f)
                                    <span class="badge bg-light text-dark border me-1">{{ $f->feature_name }}</span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No brands in this category yet. Add one above.</p>
                @endforelse
            </div>
        </div>
    @else
      
    @endif
</div>
@endsection
