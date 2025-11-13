<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = [
        'customer_segment_id',
        'name',
        'email',
        'phone',
        'address'
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CustomerProgress::class);
    }
    
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    // علاقة جديدة مع الطلبات
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // Helper Methods
    public function getTotalSpent(): float
    {
        return $this->orders()
                    ->where('payment_status', 'paid')
                    ->sum('total');
    }

    public function getOrdersCount(): int
    {
        return $this->orders()->count();
    }

    public function getPendingOrdersCount(): int
    {
        return $this->orders()
                    ->where('status', 'pending')
                    ->count();
    }

    public function getCompletedOrdersCount(): int
    {
        return $this->orders()
                    ->where('status', 'completed')
                    ->count();
    }

    public function getUnpaidAmount(): float
    {
        return $this->orders()
                    ->where('payment_status', 'unpaid')
                    ->sum('total');
    }

    public function getLastOrderDate()
    {
        return $this->orders()
                    ->latest('order_date')
                    ->first()
                    ?->order_date;
    }
}