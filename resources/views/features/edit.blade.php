@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Feature</h2>

    <form action="{{ route('features.update', $feature->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="feature_name" class="form-label">Feature Name</label>
            <input type="text" name="feature_name" id="feature_name" class="form-control" value="{{ old('feature_name', $feature->feature_name) }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Update Feature</button>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
