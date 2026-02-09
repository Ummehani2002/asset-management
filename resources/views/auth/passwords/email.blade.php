@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('account-link')
    <span class="auth-account-link"><a href="{{ route('login') }}">Back to Login</a></span>
@endsection

@section('content')

<h2 class="auth-welcome">Forgot Password?</h2>
<p class="auth-subtitle">Enter your email to receive a password reset link</p>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
    </div>

    <button type="submit" class="btn btn-auth">Send Reset Link</button>
</form>

<p class="auth-footer-text">
    <a href="{{ route('login') }}">Back to Login</a>
</p>
@endsection
