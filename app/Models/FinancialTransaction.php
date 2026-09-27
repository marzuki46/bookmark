<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'type',
        'amount',
        'description',
        'date',
        'payment_method',
        'notes',
        'source',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date', now()->month)
            ->whereYear('date', now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('date', now()->year);
    }

    public function scopeBetweenDates($query, string $start, string $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    /**
     * Inclusive date range that behaves identically on MySQL and SQLite.
     *
     * A bare whereBetween('date', [...]) is unsafe here: the date cast can
     * store 'YYYY-MM-DD 00:00:00', and a string comparison against the plain
     * 'YYYY-MM-DD' end bound then excludes the final day. Extending the upper
     * bound to end-of-day keeps the comparison correct while still using the
     * (user_id, date) index.
     */
    public function scopeInRange($query, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($to, fn ($q) => $q->where('date', '<=', $to.' 23:59:59'));
    }
}
