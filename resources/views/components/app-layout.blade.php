@extends('layouts.app')

@section('content')
    @isset($header)
        <div class="page-header mb-4">
            {{ $header }}
        </div>
    @endisset

    {{ $slot }}
@endsection

