@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Verify Your Email Address</div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <p>Thanks for signing up. Before continuing, please verify your email address by clicking the link we sent you.</p>
                    <p class="mb-0">If you did not receive the email, you can request another below.</p>

                    <form method="POST" action="{{ route('verification.send') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn btn-primary">Resend Verification Email</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
