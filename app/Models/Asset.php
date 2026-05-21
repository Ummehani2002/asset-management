<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
  protected $table = 'assets'; 
  protected $fillable = [
        'asset_id',
         'location_id',
        'entity_id',
        'asset_category_id',
        'brand_id',
        'model_number',
        'purchase_date',
        'warranty_start',
        'warranty_years',
        'expiry_date',
        'po_number',
        'vendor_name',
        'value',
        'serial_number',
        'invoice_path',
        'status',
        'os_license_key',
        'ms_office_license_key',
        'on_screen_takeoff_key',
        'antivirus_license_version',
        'patch_management_software',
        'autocad_license_key',
    ];
    public function category()
    {
       return $this->belongsTo(\App\Models\AssetCategory::class, 'asset_category_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function project() 
    {
    return $this->belongsTo(Project::class, 'project_id'); 
    }
  public function location()
{
    return $this->belongsTo(\App\Models\Location::class, 'location_id', 'id');
}

    public function entity()
    {
        return $this->belongsTo(\App\Models\Entity::class, 'entity_id');
    }

    public function features()
{
    return $this->belongsToMany(CategoryFeature::class, 'asset_feature_values', 'asset_id', 'feature_id')
        ->withPivot('value');
}
    public function brand()
{
    return $this->belongsTo(Brand::class);
}
    public function transactions()
{
    return $this->hasMany(AssetTransaction::class);
}
    public function latestTransaction()
{
    return $this->hasOne(\App\Models\AssetTransaction::class)->latestOfMany();
}


public function assetCategory()
{
    return $this->belongsTo(AssetCategory::class, 'asset_category_id');
}
public function featureValues()
{
    return $this->hasMany(CategoryFeatureValue::class, 'asset_id');
}

    /**
     * Brand model master row (model_number + default feature values).
     */
    public function linkedBrandModel(): ?BrandModel
    {
        if (!$this->brand_id || !\Illuminate\Support\Facades\Schema::hasTable('brand_models')) {
            return null;
        }

        $query = BrandModel::where('brand_id', $this->brand_id);

        if (!empty($this->model_number)) {
            $match = (clone $query)->where('model_number', $this->model_number)->first();
            if ($match) {
                return $match;
            }
        }

        // If brand has only one model, use it when asset has no model_number saved.
        if (empty($this->model_number)) {
            $models = $query->get();
            if ($models->count() === 1) {
                return $models->first();
            }
        }

        return null;
    }

    /**
     * Display model: asset field, per-asset features, then brand model master.
     */
    public function resolveDisplayModel(): string
    {
        if (!empty($this->model_number)) {
            return trim((string) $this->model_number);
        }

        foreach ($this->assetFeatureValuesCollection() as $fv) {
            $name = strtolower((string) ($fv->feature->feature_name ?? ''));
            if ($this->featureNameIsModel($name)) {
                $val = trim((string) ($fv->feature_value ?? ''));
                if ($val !== '') {
                    return $val;
                }
            }
        }

        $brandModel = $this->linkedBrandModel();
        if ($brandModel && !empty($brandModel->model_number)) {
            return trim((string) $brandModel->model_number);
        }

        foreach ($this->modelMasterFeatureValuesCollection($brandModel) as $mfv) {
            $name = strtolower((string) ($mfv->feature->feature_name ?? $mfv->categoryFeature->feature_name ?? ''));
            if ($this->featureNameIsModel($name)) {
                $val = trim((string) ($mfv->feature_value ?? ''));
                if ($val !== '') {
                    return $val;
                }
            }
        }

        return 'N/A';
    }

    /**
     * Feature lines for tables/exports: per-asset values, then brand model defaults.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function resolveFeatureEntries(): array
    {
        $entries = [];
        $seenFeatureIds = [];

        foreach ($this->assetFeatureValuesCollection() as $fv) {
            $featureId = (int) ($fv->category_feature_id ?? $fv->feature->id ?? 0);
            $label = $fv->feature->feature_name ?? 'Feature';
            if ($this->featureNameIsModel($label)) {
                continue;
            }
            foreach ($this->expandFeatureValue($label, (string) ($fv->feature_value ?? '')) as $row) {
                $entries[] = $row;
            }
            if ($featureId > 0) {
                $seenFeatureIds[$featureId] = true;
            }
        }

        $brandModel = $this->linkedBrandModel();
        foreach ($this->modelMasterFeatureValuesCollection($brandModel) as $mfv) {
            $featureId = (int) $mfv->category_feature_id;
            if ($featureId > 0 && isset($seenFeatureIds[$featureId])) {
                continue;
            }
            $label = $mfv->feature->feature_name ?? $mfv->categoryFeature->feature_name ?? 'Feature';
            if ($this->featureNameIsModel($label)) {
                continue;
            }
            foreach ($this->expandFeatureValue($label, (string) ($mfv->feature_value ?? '')) as $row) {
                $entries[] = $row;
            }
            if ($featureId > 0) {
                $seenFeatureIds[$featureId] = true;
            }
        }

        return $entries;
    }

    public function resolveFeaturesSummary(): string
    {
        $entries = $this->resolveFeatureEntries();
        if ($entries === []) {
            return 'N/A';
        }

        $parts = [];
        foreach ($entries as $entry) {
            $parts[] = $entry['label'] . ': ' . $entry['value'];
        }

        return implode('; ', $parts);
    }

    public function getDisplayModelAttribute(): string
    {
        return $this->resolveDisplayModel();
    }

    private function assetFeatureValuesCollection()
    {
        if ($this->relationLoaded('featureValues')) {
            return $this->featureValues;
        }

        return $this->featureValues()->with('feature')->get();
    }

    private function modelMasterFeatureValuesCollection(?BrandModel $brandModel = null)
    {
        $brandModel = $brandModel ?? $this->linkedBrandModel();
        if (!$brandModel || !\Illuminate\Support\Facades\Schema::hasTable('model_feature_values')) {
            return collect();
        }

        return ModelFeatureValue::where('brand_model_id', $brandModel->id)
            ->with(['categoryFeature', 'feature'])
            ->get();
    }

    private function featureNameIsModel(string $name): bool
    {
        return (bool) preg_match('/\bmodel\b|model\s*no|model\s*number|model\s*name/i', strtolower($name));
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function expandFeatureValue(string $label, string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = @json_decode($raw, true);
        if (is_array($decoded)) {
            $rows = [];
            foreach ($decoded as $subKey => $subVal) {
                $subVal = trim((string) $subVal);
                if ($subVal === '') {
                    continue;
                }
                $rows[] = [
                    'label' => $label . ' (' . $subKey . ')',
                    'value' => $subVal,
                ];
            }

            return $rows;
        }

        return [['label' => $label, 'value' => $raw]];
    }
}
