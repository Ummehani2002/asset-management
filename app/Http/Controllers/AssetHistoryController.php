<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\Entity;

class AssetHistoryController extends Controller
{
    public function show($asset_id)
    {
        $asset = Asset::with('category', 'brand')->findOrFail($asset_id);

        // Assign & Return only - tabular form
        $assignReturnHistory = AssetTransaction::with(['employee', 'location'])
            ->where('asset_id', $asset_id)
            ->whereIn('transaction_type', ['assign', 'return'])
            ->orderByRaw('COALESCE(return_date, issue_date, created_at) ASC')
            ->get()
            ->map(function ($txn) {
                $txn->entity_name = $txn->employee->entity_name ?? '-';
                $entity = $txn->employee && $txn->employee->entity_name
                    ? Entity::whereRaw('LOWER(name) = ?', [strtolower(trim($txn->employee->entity_name))])->with('assetManager')->first()
                    : null;
                $txn->asset_manager_name = $entity && $entity->assetManager ? ($entity->assetManager->name ?? $entity->assetManager->entity_name ?? '-') : '-';
                return $txn;
            });

        // System maintenance only - with remarks
        $maintenanceHistory = AssetTransaction::with(['employee'])
            ->where('asset_id', $asset_id)
            ->where('transaction_type', 'system_maintenance')
            ->orderBy('created_at', 'ASC')
            ->get();

        return view('assets.history', compact('asset', 'assignReturnHistory', 'maintenanceHistory'));
    }
}

