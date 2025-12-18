@extends('layouts.auth')

@section('content')

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
               required
               autofocus>
    </div>
    
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password"
               name="password"
               id="password"
               class="form-control"
               required>
    </div>

    <div class="mb-3 text-end">
        <a href="{{ route('password.request') }}" style="font-size: 14px;">
            Forgot Password?
        </a>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        Login
    </button>
</form>

<p class="mt-3 text-center" style="font-size: 14px; color: #666;">
    Don't have an account?
    <a href="{{ route('register.form') }}">Register</a>
</p>

@endsection
