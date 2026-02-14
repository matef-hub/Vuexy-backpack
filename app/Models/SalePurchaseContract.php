<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalePurchaseContract extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sale_purchase_contracts';

    protected $fillable = [
        'contract_number',
        'seller_name',
        'seller_entity_type',
        'seller_national_id',
        'seller_address',
        'buyer_name',
        'buyer_entity_type',
        'buyer_national_id',
        'buyer_address',
        'unit_number',
        'unit_address',
        'unit_area_sqm',
        'unit_description',
        'contract_date',
        'delivery_date',
        'currency',
        'total_price',
        'down_payment',
        'payment_method',
        'installments_count',
        'installment_amount',
        'first_installment_date',
        'status',
        'notes',
        'contract_word_file',
        'contract_word_original_name',
        'contract_word_mime',
        'contract_word_size',
        'signed_pdf_file',
        'signed_pdf_original_name',
        'signed_pdf_mime',
        'signed_pdf_size',
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
            'contract_date' => 'date',
            'delivery_date' => 'date',
            'total_price' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'installments_count' => 'integer',
            'installment_amount' => 'decimal:2',
            'first_installment_date' => 'date',
            'contract_word_size' => 'integer',
            'signed_pdf_size' => 'integer',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => (string) $this->status,
        };
    }

    public function getRemainingAmountComputedAttribute(): float
    {
        return max(0, (float) $this->total_price - (float) $this->down_payment);
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

