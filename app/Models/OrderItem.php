<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'item_type',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'specifications',
        'estimated_hours',
        'deliverables',
        'status',
        'progress_percentage',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'estimated_hours' => 'integer',
        'progress_percentage' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            $item->order->calculateTotals();
        });

        static::deleted(function ($item) {
            $item->order->calculateTotals();
        });
    }

    // Relations
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Helper Methods
    public function updateProgress(int $percentage): void
    {
        $this->progress_percentage = min(100, max(0, $percentage));
        
        if ($this->progress_percentage === 100) {
            $this->status = 'completed';
            $this->end_date = now();
        } elseif ($this->progress_percentage > 0 && $this->status === 'pending') {
            $this->status = 'in_progress';
            $this->start_date = $this->start_date ?? now();
        }
        
        $this->save();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getRemainingHours(): ?int
    {
        if (!$this->estimated_hours) return null;
        
        $completedHours = ($this->estimated_hours * $this->progress_percentage) / 100;
        return max(0, $this->estimated_hours - $completedHours);
    }
}