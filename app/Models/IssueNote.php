<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueNote extends Model
{
    use HasFactory;

  protected $fillable = [
    'employee_id',
    'department',
    'entity',
    'location',
    'system_code',
    'printer_code',
    'software_installed',
    'issued_date',
    'return_date',
    'note_type',
    'items',
    'user_signature',
    'manager_signature',
    'received_by_employee_name',
    'received_by_employee_id',
    'received_by_user_signature',
    'returned_by_employee_name',
    'returned_by_employee_id',
    'returned_by_user_signature',
    'data_backup',
    'issue_note_id', // Reference to original issue note
];

    protected $casts = [
        'items' => 'array',
        'issued_date' => 'date',
        'return_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function originalIssueNote()
    {
        return $this->belongsTo(IssueNote::class, 'issue_note_id');
    }

    public function returnNotes()
    {
        return $this->hasMany(IssueNote::class, 'issue_note_id');
    }

    public function receivedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'received_by_employee_id');
    }

    public function returnedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'returned_by_employee_id');
    }
}
