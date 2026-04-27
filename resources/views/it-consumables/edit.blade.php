@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-pencil-square me-2"></i>Edit IT Consumable</h2>
    </div>

    <div class="master-form-card">
        <form method="POST" action="{{ route('it-consumables.update', $item->id) }}" autocomplete="off">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">ID No <span class="text-danger">*</span></label>
                    <input type="text" name="id_no" class="form-control" value="{{ old('id_no', $item->id_no) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">TKT Ref No <span class="text-danger">*</span></label>
                    <input type="text" name="tkt_ref_no" class="form-control" value="{{ old('tkt_ref_no', $item->tkt_ref_no) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Item Description <span class="text-danger">*</span></label>
                    <input type="text" name="item_description" class="form-control" value="{{ old('item_description', $item->item_description) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">No. of Items <span class="text-danger">*</span></label>
                    <input type="number" min="{{ max(1, $issuedQty ?? 0) }}" name="allocated_qty" class="form-control" value="{{ old('allocated_qty', $item->allocated_qty) }}" required>
                    <small class="text-muted">Already issued: {{ $issuedQty ?? 0 }}</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Issued Date <span class="text-danger">*</span></label>
                    <input type="date" name="issued_date" class="form-control" value="{{ old('issued_date', optional($item->issued_date)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $item->remarks) }}</textarea>
                </div>
            </div>
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Update
                </button>
                <a href="{{ route('it-consumables.index') }}" class="btn btn-secondary ms-2">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
