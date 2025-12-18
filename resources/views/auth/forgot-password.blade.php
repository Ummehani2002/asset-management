@extends('layouts.auth')

@section('content')
<div class="card shadow-sm p-4" style="width: 400px;">
    <h3 class="mb-4 text-center">Reset Password</h3>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-warning w-100">Send Reset Link</button>
    </form>

    <p class="mt-3 text-center">
        <a href="{{ route('login') }}">Back to Login</a>
    </p>
</div>
@endsection
