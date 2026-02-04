@extends('layouts.app')
@section('content')
<div class="container-fluid master-page">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-cpu me-2"></i>Model: {{ $model->model_number }} <small class="text-muted">({{ $model->brand->name }})</small></h2>
        <a href="{{ route('categories.manage') }}" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to Categories</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="alert alert-info mb-3">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Type all feature values for this model here.</strong> When you add a new asset and select this model in Asset Master, these values will automatically fill in (you can still change them per asset).
    </div>
    <div class="master-form-card">
        <h5 class="mb-3">Feature values for model &ldquo;{{ $model->model_number }}&rdquo;</h5>
        <p class="text-muted small mb-3">Fill in every field you want to auto-fill in Asset Master. Leave blank if not needed.</p>
        <form action="{{ route('brand-models.update-feature-values', $model->id) }}" method="POST" autocomplete="off">
            @csrf
            @foreach($features as $feature)
                @php
                    $fv = $valuesByFeature->get($feature->id);
                    $val = $fv ? $fv->feature_value : null;
                    $subVals = $fv && $feature->sub_fields ? @json_decode($fv->feature_value, true) : [];
                    $subVals = is_array($subVals) ? $subVals : [];
                @endphp
                @if($feature->sub_fields && count($feature->sub_fields) > 0)
                    <div class="mb-3">
                        <label class="fw-bold">{{ $feature->feature_name }}</label>
                        @foreach($feature->sub_fields as $subField)
                            <div class="mb-2">
                                <label class="small text-muted">{{ $subField }}</label>
                                <input type="text" name="features_{{ $feature->id }}_{{ $subField }}" class="form-control" value="{{ $subVals[$subField] ?? '' }}" placeholder="{{ $subField }}">
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mb-3">
                        <label class="fw-bold">{{ $feature->feature_name }}</label>
                        <input type="text" name="features_{{ $feature->id }}" class="form-control" value="{{ $val ?? '' }}" placeholder="{{ $feature->feature_name }}">
                    </div>
                @endif
            @endforeach
            <button type="submit" class="btn btn-primary">Save feature values</button>
            <a href="{{ route('categories.manage') }}" class="btn btn-secondary ms-2">Cancel</a>
        </form>
    </div>
</div>
@endsection
