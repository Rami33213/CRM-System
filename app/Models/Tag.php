<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'description'
    ];

    protected static function boot()
    {
        parent::boot();

        // توليد slug تلقائياً
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * علاقة Many to Many مع Customers
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_tag')
            ->withTimestamps();
    }

    /**
     * عدد العملاء المرتبطين بهذا التاغ
     */
    public function getCustomersCountAttribute(): int
    {
        return $this->customers()->count();
    }

    /**
     * Scope للبحث بالاسم
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
            ->orWhere('slug', 'LIKE', "%{$search}%");
    }

    /**
     * Scope للتاغات الشائعة (الأكثر استخداماً)
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->withCount('customers')
            ->orderBy('customers_count', 'desc')
            ->limit($limit);
    }
}