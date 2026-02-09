@extends('layouts.auth')

@section('title', 'Login')

@section('account-link')
    <span class="auth-account-link">Don't have an account? <a href="{{ route('register.form') }}">Sign Up</a></span>
@endsection

@section('content')

<h2 class="auth-welcome">Welcome to Tanseeq!</h2>
<p class="auth-subtitle">Sign in to access your asset management dashboard</p>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('login.submit') }}">
    @csrf

    <div class="mb-3">
        <label for="username" class="form-label">Username or Email</label>
        <input type="text"
               name="username"
               id="username"
               class="form-control"
               placeholder="Enter your username or email"
               required
               autofocus>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password"
               name="password"
               id="password"
               class="form-control"
               placeholder="Enter your password"
               required>
    </div>

    <div class="auth-forgot">
        <a href="{{ route('password.request') }}">Forgot your password?</a>
    </div>

    <button type="submit" class="btn btn-auth">Log In</button>
</form>

<p class="auth-footer-text">
    Don't have an account? <a href="{{ route('register.form') }}">Sign Up</a>
</p>

@endsection
