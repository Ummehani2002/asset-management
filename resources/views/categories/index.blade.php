@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Asset Categories</h2>
    <a href="{{ route('categories.create') }}" class="btn btn-primary mb-3">Add Category</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Category</th>
                <th>Brands & Features</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $category)
            <tr>
                <td>
                    {{ $category->category_name }}
                    <br>
                    <a href="{{ route('brands.create', ['asset_category_id' => $category->id]) }}">Add Brand</a>
                </td>
                <td>
                    @foreach($category->brands as $brand)
                        <strong>{{ $brand->brand_name }}</strong>
                        <a href="{{ route('features.create', ['brand_id' => $brand->id]) }}">Add Feature</a>
                        <ul>
                            @foreach($brand->features as $feature)
                                <li>{{ $feature->feature_name }}</li>
                            @endforeach
                        </ul>
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
