<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'position',
        'department',
        'status',
        'hire_date',
        'salary',
        'address',
        'avatar',
        'permissions'
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'hire_date' => 'date',
        'permissions' => 'array'
    ];

    protected $hidden = [
        'salary' // إخفاء الراتب من الـ API العامة
    ];

    /**
     * علاقة One to Many مع Customers
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * عدد العملاء المرتبطين بالموظف
     */
    public function getCustomersCountAttribute(): int
    {
        return $this->customers()->count();
    }

    /**
     * إجمالي مبيعات عملاء الموظف
     */
    public function getTotalSalesAttribute(): float
    {
        return $this->customers()
            ->withSum('orders', 'total')
            ->get()
            ->sum('orders_sum_total') ?? 0;
    }

    /**
     * العملاء النشطين (لديهم طلبات)
     */
    public function getActiveCustomersCountAttribute(): int
    {
        return $this->customers()
            ->has('orders')
            ->count();
    }

    /**
     * Scope للموظفين النشطين
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope للموظفين حسب القسم
     */
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope للموظفين حسب الحالة
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * التحقق من أن الموظف نشط
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * عدد سنوات الخدمة
     */
    public function getYearsOfServiceAttribute(): float
    {
        if (!$this->hire_date) return 0;
        return round($this->hire_date->diffInYears(now()), 1);
    }

    /**
     * الراتب المنسق (للاستخدام الداخلي فقط)
     */
    public function getFormattedSalaryAttribute(): string
    {
        return number_format($this->salary, 2) . ' USD';
    }
}