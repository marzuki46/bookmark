<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'inv_number',
        'client_name',
        'client_address',
        'client_email',
        'date_issue',
        'date_due',
        'status',
        'work_status',
        'internal_deadline',
        'tax_rate',
        'tax_amount',
        'grand_total',
    ];

    protected function casts(): array
    {
        return [
            'date_issue' => 'date',
            'date_due' => 'date',
            'internal_deadline' => 'date',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function getRemainingAttribute(): float
    {
        return (float) $this->grand_total - $this->total_paid;
    }
}
