@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
    </div>

    <!-- Entity filter -->
    @if(isset($entities) && $entities->isNotEmpty())
    <div class="master-form-card mb-4">
        <form method="GET" action="{{ route('dashboard') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Entity</label>
                <select name="entity" class="form-control" onchange="this.form.submit()">
                    <option value="">All entities</option>
                    @foreach($entities as $ent)
                        <option value="{{ $ent->id }}" {{ (isset($selectedEntityId) && $selectedEntityId == $ent->id) ? 'selected' : '' }}>{{ ucwords($ent->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Apply</button>
            </div>
        </form>
    </div>
    @endif

    <!-- Asset Categories Card -->
    <div class="table-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 style="color: white; margin: 0;"><i class="bi bi-grid-3x3-gap me-2"></i>Asset Categories @if(isset($selectedEntity)) ({{ ucwords($selectedEntity->name) }}) @endif</h5>
            <div class="d-flex gap-3 align-items-center">
                <span class="text-white"><i class="bi bi-box-seam me-1"></i><strong>Total:</strong> {{ number_format($totalAssets ?? 0) }}</span>
                <span class="text-white"><i class="bi bi-check-circle me-1"></i><strong>Available:</strong> {{ number_format($availableAssets ?? 0) }}</span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-center">Total Assets</th>
                            <th class="text-center">Available</th>
                            <th class="text-center">Assigned</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categoryCounts as $category)
                            <tr>
                                <td class="fw-medium">
                                    {{ $category->category_name }}
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-secondary">
                                        {{ $category->assets_count }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-success">
                                        {{ $category->available_count ?? 0 }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-primary">
                                        {{ $category->assigned_count ?? 0 }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <a href="{{ route('assets.byCategory', $category->id) }}{{ isset($selectedEntityId) ? '?entity=' . $selectedEntityId : '' }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View Assets
                                    </a>
                                </td>
                            </tr>
                        @endforeach

                        @if($categoryCounts->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No categories found
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
