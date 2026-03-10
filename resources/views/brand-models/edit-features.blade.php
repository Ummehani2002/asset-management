@extends('layouts.app')
@section('content')
<div class="container-fluid master-page">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="mb-0"><i class="bi bi-cpu me-2"></i>Model: {{ $model->model_number }} <small class="text-muted">({{ $model->brand->name }})</small></h2>
        <div class="d-flex gap-1">
            <a href="{{ route('brand-models.edit', $model->id) }}?return_url={{ urlencode(url()->current()) }}" class="btn btn-outline-warning"><i class="bi bi-pencil me-1"></i>Edit model</a>
            <form action="{{ route('brand-models.destroy', $model->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this model and its feature values?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete model</button>
            </form>
            <a href="{{ route('categories.manage') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancel</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Set values beside table: one row per feature, value input in same row — no scrolling --}}
    <div class="master-table-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0" style="color: white;"><i class="bi bi-list-check me-2"></i>Set values for &ldquo;{{ $model->model_number }}&rdquo;</h5>
            <div class="d-flex gap-1">
                <a href="{{ route('brand-models.edit', $model->id) }}?return_url={{ urlencode(url()->current()) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i> Edit</a>
                <form action="{{ route('brand-models.destroy', $model->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this model and its feature values?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger text-white border-white"><i class="bi bi-trash"></i> Delete</button>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <form action="{{ route('brand-models.update-feature-values', $model->id) }}" method="POST" autocomplete="off">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 22%;">Feature name</th>
                                <th style="width: 28%;">Sub fields</th>
                                <th style="width: 45%;">Set value(s) <span class="text-muted fw-normal small">— type here, same row</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($features as $index => $feature)
                                @php
                                    $fv = $valuesByFeature->get($feature->id);
                                    $val = $fv ? $fv->feature_value : null;
                                    $subVals = $fv && $feature->sub_fields ? @json_decode($fv->feature_value, true) : [];
                                    $subVals = is_array($subVals) ? $subVals : [];
                                    $isModelNumber = strtolower($feature->feature_name ?? '') === 'model number';
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $index + 1 }}</td>
                                    <td><strong>{{ $feature->feature_name }}</strong></td>
                                    <td>
                                        @if($feature->sub_fields && count($feature->sub_fields) > 0)
                                            <span class="text-muted small">{{ implode(', ', $feature->sub_fields) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="bg-light">
                                        @if($feature->sub_fields && count($feature->sub_fields) > 0)
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                @foreach($feature->sub_fields as $subField)
                                                    <div class="input-group input-group-sm" style="min-width: 120px;">
                                                        <span class="input-group-text text-muted small" style="max-width: 80px; overflow: hidden; text-overflow: ellipsis;" title="{{ $subField }}">{{ $subField }}</span>
                                                        <input type="text" name="features_{{ $feature->id }}_{{ $subField }}" class="form-control form-control-sm" value="{{ $subVals[$subField] ?? '' }}" placeholder="{{ $subField }}">
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <input type="text" name="features_{{ $feature->id }}" class="form-control form-control-sm" value="{{ $isModelNumber ? ($model->model_number ?? '') : ($val ?? '') }}" placeholder="{{ $feature->feature_name }}" {{ $isModelNumber ? 'readonly' : '' }}>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top bg-white d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save feature values</button>
                    <a href="{{ route('brand-models.edit', $model->id) }}?return_url={{ urlencode(url()->current()) }}" class="btn btn-outline-warning btn-sm">Edit model</a>
                    <form action="{{ route('brand-models.destroy', $model->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this model and its feature values?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete model</button>
                    </form>
                    <a href="{{ route('categories.manage') }}" class="btn btn-secondary ms-auto">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
