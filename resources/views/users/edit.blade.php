
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit User</h2>
    <form action="{{ route('users.update', $user->id) }}" method="POST" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
        </div>
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="text" name="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="off" inputmode="email">
        </div>
        <div class="mb-3">
            <label>New Password (leave blank to keep current)</label>
            <input type="password" name="password" class="form-control">
        </div>
        <div class="mb-3">
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-control">
        </div>

        <div class="mb-3">
            <label>Role <span class="text-danger">*</span></label>
            <select name="role" class="form-control" required>
                <option value="user" {{ old('role', $user->role ?? 'user') == 'user' ? 'selected' : '' }}>User</option>
                <option value="admin" {{ old('role', $user->role ?? 'user') == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
            <small class="text-muted">Admin can access all features. User has limited access.</small>
        </div>

        <div class="mb-3">
            <label>Link to Employee (for Asset Manager)</label>
            <select name="employee_id" class="form-control">
                <option value="">-- No employee link --</option>
                @foreach($employees ?? [] as $emp)
                    <option value="{{ $emp->id }}" {{ old('employee_id', $user->employee_id) == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name ?? $emp->entity_name ?? 'N/A' }} ({{ $emp->employee_id ?? '' }})
                    </option>
                @endforeach
            </select>
            <small class="text-muted">When this user is set as Asset Manager for an entity (in Asset Manager), they will only see and manage that entity's assets.</small>
        </div>

        <button type="submit" class="btn btn-primary">Update User</button>
        <button type="button" class="btn btn-secondary ms-2" onclick="resetForm(this)">
            <i class="bi bi-x-circle me-2"></i>Cancel
        </button>
    </form>
</div>
@endsection