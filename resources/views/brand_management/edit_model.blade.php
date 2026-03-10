@extends('layouts.app')
@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-pencil me-2"></i>Edit Model</h2>
        <p class="text-muted mb-0">{{ $model->brand->name ?? 'Brand' }} — {{ $model->model_number }}</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('brand-models.update', $model->id) }}" method="POST">
                @csrf
                @method('PUT')
                @if(request('return_url'))
                    <input type="hidden" name="return_url" value="{{ request('return_url') }}">
                @endif
                <div class="mb-3">
                    <label class="form-label">Model number <span class="text-danger">*</span></label>
                    <input type="text" name="model_number" class="form-control" value="{{ old('model_number', $model->model_number) }}" required maxlength="255">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                    <a href="{{ request('return_url', url()->previous()) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
