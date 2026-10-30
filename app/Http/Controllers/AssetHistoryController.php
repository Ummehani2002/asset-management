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
        $data = $this->historyData($asset_id);

        if (request()->boolean('partial')) {
            return view('partials.asset_history_content', $data);
        }

        return view('assets.history', $data);
    }

    private function historyData($asset_id): array
    {
        $asset = Asset::with('category', 'brand', 'location')->findOrFail($asset_id);

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

        $maintenanceHistory = AssetTransaction::with(['employee'])
            ->where('asset_id', $asset_id)
            ->where('transaction_type', 'system_maintenance')
            ->orderBy('created_at', 'ASC')
            ->get();

        return compact('asset', 'assignReturnHistory', 'maintenanceHistory');
    }
}

