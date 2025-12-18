<!DOCTYPE html>
<html>
<head>
    <title>Category Report - {{ $category->category_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1F2A44; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        h2 { color: #333; }
        .brand-section { margin-top: 20px; }
        .brand-header { background-color: #4CAF50; color: white; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Category Report: {{ $category->category_name }}</h2>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Brands:</strong> {{ $category->brands->count() }}</p>
    
    @foreach($category->brands as $brand)
        <div class="brand-section">
            <h3 style="color: #4CAF50;">Brand: {{ $brand->name }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>Feature Name</th>
                        <th>Sub Fields</th>
                    </tr>
                </thead>
                <tbody>
                    @if($brand->features->count() > 0)
                        @foreach($brand->features as $feature)
                            <tr>
                                <td>{{ $feature->feature_name }}</td>
                                <td>
                                    @if($feature->sub_fields && is_array($feature->sub_fields) && count($feature->sub_fields) > 0)
                                        {{ implode(', ', $feature->sub_fields) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="2" class="text-center text-muted">No features added</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>

