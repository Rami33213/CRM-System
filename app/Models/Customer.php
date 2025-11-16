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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getTotalSpentAttribute(): float
    {
        return $this->orders()->sum('total');
    }

    public function getTotalOrdersAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getPendingOrdersAttribute(): int
    {
        return $this->orders()->where('status', 'pending')->count();
    }

    public function getCompletedOrdersAttribute(): int
    {
        return $this->orders()->where('status', 'completed')->count();
    }

    public function getUnpaidAmountAttribute(): float
    {
        return $this->orders()
            ->where('payment_status', '!=', 'paid')
            ->sum('total') - $this->orders()
            ->where('payment_status', '!=', 'paid')
            ->sum('paid_amount');
    }
}