@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
      
    </div>

    <!-- Asset Categories Card -->
    <div class="table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;"><i class="bi bi-grid-3x3-gap me-2"></i>Asset Categories</h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-center">Total Assets</th>
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
                                    <a href="{{ route('assets.byCategory', $category->id) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View Assets
                                    </a>
                                </td>
                            </tr>
                        @endforeach

                        @if($categoryCounts->isEmpty())
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
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
