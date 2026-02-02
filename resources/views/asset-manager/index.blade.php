@extends('layouts.app')

@section('content')
@php
    $entities = $entities ?? collect([]);
@endphp
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-person-gear me-2"></i>Asset Manager</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>Assign Asset Manager per Entity</h5>
        </div>
       
        <div class="card-body p-0 pt-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Entity</th>
                            <th>Current Asset Manager</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($entities->count() > 0)
                            @foreach($entities as $ent)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ ucwords($ent->name) }}</td>
                                    <td>
                                        @if($ent->assetManager)
                                            {{ $ent->assetManager->name ?? $ent->assetManager->entity_name ?? 'N/A' }} ({{ $ent->assetManager->employee_id ?? '' }})
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('asset-manager.edit', $ent->id) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-person-plus me-1"></i>Assign / Change
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No entities found. Add entities first in <a href="{{ route('entity-master.index') }}">Entity Master</a>.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
