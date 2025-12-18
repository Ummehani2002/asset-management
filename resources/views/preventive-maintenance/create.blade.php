@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Preventive Maintenance</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('preventive-maintenance.store') }}" class="card p-4">
        @csrf

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="asset_id" class="form-label">Select Asset (Category - Serial Number)</label>
                <select id="asset_id" name="asset_id" class="form-control" required>
                    <option value="">-- Select Asset --</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}">
                            {{ $asset->assetCategory->category_name ?? 'N/A' }} - {{ $asset->serial_number }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label for="asset_category" class="form-label">Asset Category</label>
                <input type="text" id="asset_category" class="form-control" readonly>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="serial_number" class="form-label">Serial Number</label>
                <input type="text" id="serial_number" class="form-control" readonly>
            </div>

            <div class="col-md-6">
                <label for="brand" class="form-label">Brand</label>
                <input type="text" id="brand" class="form-control" readonly>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="maintenance_date" class="form-label">Maintenance Date</label>
                <input type="date" id="maintenance_date" name="maintenance_date" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label for="next_maintenance_date" class="form-label">Next Maintenance Date (Auto +90 days)</label>
                <input type="date" id="next_maintenance_date" class="form-control" readonly>
            </div>
        </div>

        <div class="mb-3">
            <label for="maintenance_description" class="form-label">Maintenance Description</label>
            <textarea id="maintenance_description" name="maintenance_description" class="form-control" rows="4"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Schedule Maintenance</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const assetSelect = document.getElementById('asset_id');
    const maintenanceDateInput = document.getElementById('maintenance_date');

    // Fetch asset details when asset is selected
    assetSelect.addEventListener('change', function() {
        const assetId = this.value;

        if (!assetId) {
            document.getElementById('asset_category').value = '';
            document.getElementById('serial_number').value = '';
            document.getElementById('brand').value = '';
            return;
        }

        fetch(`/asset/${assetId}/details`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                document.getElementById('asset_category').value = data.category;
                document.getElementById('serial_number').value = data.serial_number;
                document.getElementById('brand').value = data.brand;
            })
            .catch(error => console.error('Error:', error));
    });

    // Calculate next maintenance date (90 days from maintenance_date)
    maintenanceDateInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        if (!isNaN(selectedDate)) {
            const nextDate = new Date(selectedDate.getTime() + (90 * 24 * 60 * 60 * 1000));
            const nextDateString = nextDate.toISOString().split('T')[0];
            document.getElementById('next_maintenance_date').value = nextDateString;
        }
    });
});
</script>
@endsection