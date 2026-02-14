<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyDocument extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'company_documents';

    protected $fillable = [
        'docname',
        'doc_number',
        'doc_type',
        'doc_issue_date',
        'doc_end_date',
        'doc_file',
        'doc_original_name',
        'doc_mime',
        'doc_size',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'doc_issue_date' => 'date',
            'doc_end_date' => 'date',
            'doc_size' => 'integer',
        ];
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

