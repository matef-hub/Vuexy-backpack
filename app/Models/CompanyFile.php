<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CompanyFile extends Model
{
    use CrudTrait;

    protected $fillable = [
        'file_number',
        'file_name',
        'issuing_authority',
        'issue_date',
        'expiry_date',
        'is_active',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function getIsCurrentlyValidAttribute(): bool
    {
        $today = Carbon::today();

        return (bool) $this->is_active
            && (is_null($this->expiry_date) || $this->expiry_date->greaterThanOrEqualTo($today));
    }

    public function getStatusTextAttribute(): string
    {
        return $this->is_currently_valid ? 'ساري' : 'غير ساري';
    }
}
