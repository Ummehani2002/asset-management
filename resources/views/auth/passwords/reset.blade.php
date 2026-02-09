@extends('layouts.auth')

@section('title', 'Reset Password')

@section('account-link')
    <span class="auth-account-link"><a href="{{ route('login') }}">Back to Login</a></span>
@endsection

@section('content')

<h2 class="auth-welcome">Reset Password</h2>
<p class="auth-subtitle">Enter your new password below</p>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email" class="form-control" placeholder="Your email" required>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">New Password</label>
        <input type="password" name="password" id="password" class="form-control" placeholder="New password" required>
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Confirm password" required>
    </div>

    <button type="submit" class="btn btn-auth">Reset Password</button>
</form>

<p class="auth-footer-text">
    <a href="{{ route('login') }}">Back to Login</a>
</p>
@endsection
