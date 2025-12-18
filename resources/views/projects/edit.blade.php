@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Edit Project</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('projects.update', $project->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Project ID</label>
            <input name="project_id" class="form-control" value="{{ old('project_id', $project->project_id) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Project Name</label>
            <input name="project_name" class="form-control" value="{{ old('project_name', $project->project_name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Entity</label>
            <select name="entity" class="form-control">
                <option value="">-- Select Entity --</option>
                @foreach($entities as $ent)
                    <option value="{{ $ent }}" @if(old('entity', $project->entity) == $ent) selected @endif>{{ $ent }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Project Manager</label>
            <select name="project_manager" class="form-control">
                <option value="">-- Select Manager --</option>
                @foreach($employees as $e)
                    <option value="{{ $e->name ?? $e->entity_name }}" 
                        @if(old('project_manager', $project->project_manager) == ($e->name ?? $e->entity_name)) selected @endif>
                        {{ $e->name ?? $e->entity_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">PC Secretary</label>
            <select name="pc_secretary" class="form-control">
                <option value="">-- Select PC Secretary --</option>
                @foreach($employees as $e)
                    <option value="{{ $e->name ?? $e->entity_name }}" 
                        @if(old('pc_secretary', $project->pc_secretary) == ($e->name ?? $e->entity_name)) selected @endif>
                        {{ $e->name ?? $e->entity_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-primary">Update Project</button>
        <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
