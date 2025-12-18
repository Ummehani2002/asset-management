@extends('layouts.auth')

@section('content')
<div class="card shadow-sm p-4" style="width: 400px;">
    <h3 class="mb-4 text-center">Reset Password</h3>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <input type="email" name="email" class="form-control mb-2" placeholder="Email Address" required>
        <button type="submit" class="btn btn-warning w-100">Send Reset Link</button>
    </form>

    <p class="mt-3 text-center">
        <a href="{{ route('login') }}">Back to Login</a>
    </p>
</div>
@endsection
