@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Project Details</h3>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Project ID:</strong> {{ $project->project_id }}</p>
            <p><strong>Project Name:</strong> {{ $project->project_name }}</p>
            <p><strong>Entity:</strong> {{ $project->entity ?? '-' }}</p>
            <p><strong>Project Manager:</strong> {{ $project->project_manager ?? '-' }}</p>
            <p><strong>PC Secretary:</strong> {{ $project->pc_secretary ?? '-' }}</p>
        </div>
    </div>

    <a href="{{ route('projects.index') }}" class="btn btn-secondary">Back to Projects</a>
    <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-primary">Edit Project</a>
</div>
@endsection
