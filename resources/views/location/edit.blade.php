@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Location</h2>
@if ($errors->any())
  <div class="alert alert-danger">
    <ul>
      @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

    <form action="{{ route('location.update', $location->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="location_name" class="form-label">Location Name</label>
            <input type="text" name="location_name" value="{{ old('location_name', $location->location_name) }}" class="form-control" required>


        </div>

      

        <button type="submit" class="btn btn-primary">Update Location</button>
    </form>
</div>
@endsection
