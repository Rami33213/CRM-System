<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'order_number',
        'status',
        'payment_status',
        'source',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'notes',
        'customer_requirements',
        'internal_notes',
        'created_by',
        'assigned_to'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    protected $with = ['items', 'customer'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
            if (empty($order->order_date)) {
                $order->order_date = now();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastOrder = self::withTrashed()
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->latest('id')
                        ->first();
        
        $number = $lastOrder ? intval(substr($lastOrder->order_number, -4)) + 1 : 1;
        
        return sprintf('ORD-%s%s-%04d', $year, $month, $number);
    }

    // Relations
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Helper Methods
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total_price');
        $this->tax_amount = ($this->subtotal * $this->tax_rate) / 100;
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->save();
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getProgressPercentage(): int
    {
        $totalItems = $this->items->count();
        if ($totalItems === 0) return 0;
        
        $avgProgress = $this->items->avg('progress_percentage');
        return round($avgProgress);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}