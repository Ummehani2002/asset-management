@extends('layouts.work-log-app')

@section('title', 'Login')
@section('header')
@endsection
@section('hide-nav')

@section('content')
<div class="login-page" style="margin: -16px; padding: 24px;">
    <div class="login-logo">
        <i class="bi bi-clock-history"></i>
        <h1>Tanseeq Work Log</h1>
        <p>Log your daily tasks from your phone</p>
    </div>

    <div class="login-card">
        @if($errors->any())
            <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('worklog.login.submit') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <input type="text" name="username" class="form-control" required autofocus
                       placeholder="Enter username or email">
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required
                       placeholder="Enter password">
            </div>
            <button type="submit" class="btn-app">Sign In</button>
        </form>
    </div>
</div>
@endsection
