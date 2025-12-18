@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Brands List</h1>

        @if($brands->isEmpty())
            <p>No brands found.</p>
        @else
            <ul>
                @foreach ($brands as $brand)
                    <li>{{ $brand->name }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
