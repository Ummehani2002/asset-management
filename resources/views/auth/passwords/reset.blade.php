@extends('layouts.auth')

@section('content')
<div class="card shadow-sm p-4" style="width: 400px;">
    <h3 class="mb-4 text-center">Reset Password</h3>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
        <input type="password" name="password" class="form-control mb-2" placeholder="New Password" required>
        <input type="password" name="password_confirmation" class="form-control mb-2" placeholder="Confirm Password" required>

        <button type="submit" class="btn btn-warning w-100">Reset Password</button>
    </form>

    <p class="mt-3 text-center">
        <a href="{{ route('login') }}">Back to Login</a>
    </p>
</div>
@endsection
