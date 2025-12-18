<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Report - Asset Categories</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        h2 { color: #333; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>Dashboard Report - Asset Categories</h2>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Categories:</strong> {{ $categoryCounts->count() }}</p>
    <p><strong>Total Assets:</strong> {{ $categoryCounts->sum('assets_count') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Category Name</th>
                <th class="text-center">Total Assets</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categoryCounts as $index => $category)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $category->category_name }}</td>
                    <td class="text-center">{{ $category->assets_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

