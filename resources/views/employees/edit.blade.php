@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Employee</h2>
@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

    <form action="{{ route('employees.update', $employee->id) }}" method="POST" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" value="{{ old('name', $employee->name) }}" class="form-control" required>
        </div>



        <button type="submit" class="btn btn-primary">Update</button>
        <button type="button" class="btn btn-secondary ms-2" onclick="resetForm(this)">
            <i class="bi bi-x-circle me-2"></i>Cancel
        </button>
    </form>
</div>
@endsection
