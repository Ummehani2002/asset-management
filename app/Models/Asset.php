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
     * Display model: assets.model_number, else category feature whose name contains "model".
     */
    public function resolveDisplayModel(): string
    {
        if (!empty($this->model_number)) {
            return trim((string) $this->model_number);
        }

        foreach ($this->relationLoaded('featureValues') ? $this->featureValues : $this->featureValues()->with('feature')->get() as $fv) {
            $name = strtolower((string) ($fv->feature->feature_name ?? ''));
            if (preg_match('/\bmodel\b|model\s*no|model\s*number|model\s*name/i', $name)) {
                $val = trim((string) ($fv->feature_value ?? ''));
                if ($val !== '') {
                    return $val;
                }
            }
        }

        return 'N/A';
    }

    public function getDisplayModelAttribute(): string
    {
        return $this->resolveDisplayModel();
    }
}
