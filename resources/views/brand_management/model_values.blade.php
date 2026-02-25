@extends('layouts.app')
@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-list-check me-2"></i>Model Values</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Step 1: Select category — form stays blank until category selected --}}
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Select category</h5>
        <div class="row align-items-end">
            <div class="col-md-5">
                <label for="sel_category" class="form-label">Category</label>
                <select id="sel_category" class="form-select" onchange="goCategory(this.value)">
                    <option value="{{ route('brand-management.model-values') }}">— Select category —</option>
                    @foreach($categories as $cat)
                        <option value="{{ route('brand-management.model-values', ['category_id' => $cat->id]) }}" {{ $selectedCategoryId == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if($selectedCategoryId)
        {{-- Step 2: Select brand — only shown when category selected --}}
        <div class="master-form-card mb-4">
            <h5 class="mb-3"><i class="bi bi-tag me-2"></i>Select brand</h5>
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label for="sel_brand" class="form-label">Brand</label>
                    <select id="sel_brand" class="form-select" onchange="goBrand(this.value)">
                        <option value="{{ route('brand-management.model-values', ['category_id' => $selectedCategoryId]) }}">— Select brand —</option>
                        @foreach($brands as $b)
                            <option value="{{ route('brand-management.model-values', ['category_id' => $selectedCategoryId, 'brand_id' => $b->id]) }}" {{ $selectedBrandId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    @endif

    @if($selectedBrandId)
        {{-- Step 3: Select model — only shown when brand selected --}}
        <div class="master-form-card mb-4">
            <h5 class="mb-3"><i class="bi bi-cpu me-2"></i>Select model</h5>
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label for="sel_model" class="form-label">Model number</label>
                    <select id="sel_model" class="form-select" onchange="goModel(this.value)">
                        <option value="{{ route('brand-management.model-values', ['category_id' => $selectedCategoryId, 'brand_id' => $selectedBrandId]) }}">— Select model —</option>
                        @foreach($models as $m)
                            <option value="{{ route('brand-management.model-values', ['category_id' => $selectedCategoryId, 'brand_id' => $selectedBrandId, 'model_id' => $m->id]) }}" {{ $selectedModelId == $m->id ? 'selected' : '' }}>{{ $m->model_number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    @endif

    @if($model && $selectedModelId)
        @php $selectedCategory = $categories->firstWhere('id', $selectedCategoryId); @endphp
        {{-- Step 4: Add/edit model feature values — only shown when model selected --}}
        <div class="master-table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: white;"><i class="bi bi-list-check me-2"></i>Set values for model &ldquo;{{ $model->model_number }}&rdquo; ({{ $model->brand->name ?? '' }}) — <span class="badge bg-info">{{ $selectedCategory->category_name ?? 'N/A' }}</span></h5>
            </div>
            <div class="card-body p-0">
                <form action="{{ route('brand-models.update-feature-values', $model->id) }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="category_id" value="{{ $selectedCategoryId }}">
                    <input type="hidden" name="return_to" value="model_values">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 25%;">Feature</th>
                                    <th style="width: 70%;">Value(s)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($features as $index => $feature)
                                    @php
                                        $fv = $valuesByFeature->get($feature->id);
                                        $val = $fv ? $fv->feature_value : null;
                                        $subVals = $fv && $feature->sub_fields ? @json_decode($fv->feature_value, true) : [];
                                        $subVals = is_array($subVals) ? $subVals : [];
                                        $isModelNumber = strtolower($feature->feature_name ?? '') === 'model number';
                                        $modelNumVal = $model->model_number ?? '';
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $index + 1 }}</td>
                                        <td><strong>{{ $feature->feature_name }}</strong></td>
                                        <td class="bg-light">
                                            @if($feature->sub_fields && count($feature->sub_fields) > 0)
                                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                                    @foreach($feature->sub_fields as $subField)
                                                        <input type="text" name="features_{{ $feature->id }}_{{ $subField }}" class="form-control form-control-sm" style="min-width: 120px;" value="{{ $subVals[$subField] ?? '' }}" placeholder="{{ $subField }}">
                                                    @endforeach
                                                </div>
                                            @else
                                                <input type="text" name="features_{{ $feature->id }}" class="form-control form-control-sm" value="{{ $isModelNumber ? $modelNumVal : ($val ?? '') }}" placeholder="{{ $feature->feature_name }}" {{ $isModelNumber ? 'readonly' : '' }}>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted text-center py-3">No features defined for this brand. Add features in <a href="{{ route('brand-management.add-brand-model', ['category_id' => $selectedCategoryId]) }}">Add Brand & Model</a> first.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($features->isNotEmpty())
                        <div class="p-3 border-top bg-white">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Save feature values</button>
                            <a href="{{ route('brand-management.model-values', ['category_id' => $selectedCategoryId, 'brand_id' => $selectedBrandId]) }}" class="btn btn-secondary btn-sm ms-2">Change model</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    @elseif($selectedCategoryId && $brands->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>No brands in this category. Add brands in <a href="{{ route('brand-management.add-brand-model', ['category_id' => $selectedCategoryId]) }}">Add Brand & Model</a>.
        </div>
    @elseif($selectedBrandId && $models->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>No models for this brand. Add models in <a href="{{ route('brand-management.add-brand-model', ['category_id' => $selectedCategoryId]) }}">Add Brand & Model</a>.
        </div>
    @endif

    @if(!$selectedCategoryId)
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>Select a category above to choose brand and model, then add or edit model values.
        </div>
    @endif
</div>
<script>
function goCategory(url) { if (url) window.location.href = url; }
function goBrand(url) { if (url) window.location.href = url; }
function goModel(url) { if (url) window.location.href = url; }
</script>
@endsection
