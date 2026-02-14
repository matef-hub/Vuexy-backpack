<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalContract extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'rental_contracts';

    protected $fillable = [
        'contract_number',
        'landlord_name',
        'landlord_entity_type',
        'landlord_national_id',
        'landlord_address',
        'tenant_name',
        'tenant_entity_type',
        'tenant_national_id',
        'tenant_address',
        'unit_number',
        'unit_address',
        'unit_area_sqm',
        'lease_duration_months',
        'lease_start_date',
        'lease_end_date',
        'monthly_rent',
        'security_deposit',
        'contract_file',
        'contract_original_name',
        'contract_mime',
        'contract_size',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_area_sqm' => 'decimal:2',
            'lease_duration_months' => 'integer',
            'lease_start_date' => 'date',
            'lease_end_date' => 'date',
            'monthly_rent' => 'decimal:2',
            'security_deposit' => 'decimal:2',
            'contract_size' => 'integer',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'active' => 'نشط',
            'expired' => 'منتهي',
            'terminated' => 'منتهي بالفسخ',
            default => (string) $this->status,
        };
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
