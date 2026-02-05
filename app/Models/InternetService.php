<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternetService extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'project_name',
        'entity',
        'service_type',
        'bandwidth',
        'transaction_type',
        'account_number',
        'service_start_date',
        'service_end_date',
        'person_in_charge_id',
        'person_in_charge',
        'contact_details',
        'project_manager_id',
        'project_manager',
        'pm_contact_number',
        'document_controller_id',
        'document_controller',
        'document_controller_number',
        'mrc',
        'cost',
        'pr_number',
        'po_number',
        'status'
    ];

    protected $casts = [
        'service_start_date' => 'date',
        'service_end_date'   => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function personInCharge()
    {
        return $this->belongsTo(Employee::class, 'person_in_charge_id');
    }

    public function projectManager()
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }
    
    public function documentController()
    {
        return $this->belongsTo(Employee::class, 'document_controller_id');
    }
}
