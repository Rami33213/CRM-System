<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = [
        'customer_segment_id',
        'employee_id',
        'name',
        'email',
        'phone',
        'address'
        // تم إزالة score
    ];

    // تم إزالة casts للـ score

    public function segment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id');
    }

    // علاقة مع Employee
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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

    // علاقة مع QuizResult (عدة أسطر لنفس العميل)
    public function quizResults()
    {
        return $this->hasMany(QuizResult::class, 'phone', 'phone');
    }

    // حساب Score من quiz_results ديناميكياً
    public function getScoreAttribute(): float
    {
        if (!$this->phone) return 0;
        return QuizResult::calculateTotalScoreByPhone($this->phone);
    }

    // حساب النسبة المئوية
    public function getQuizPercentageAttribute(): float
    {
        if (!$this->phone) return 0;
        return QuizResult::calculatePercentageByPhone($this->phone);
    }

    // الحصول على Grade
    public function getQuizGradeAttribute(): string
    {
        if (!$this->phone) return 'N/A';
        return QuizResult::getGradeByPhone($this->phone);
    }

    // عدد الأسئلة المجابة
    public function getQuizQuestionsCountAttribute(): int
    {
        if (!$this->phone) return 0;
        return QuizResult::getQuestionsCountByPhone($this->phone);
    }

    // إحصائيات الاختبار الكاملة
    public function getQuizStatsAttribute(): array
    {
        if (!$this->phone) {
            return [
                'total_score' => 0,
                'percentage' => 0,
                'grade' => 'N/A',
                'questions_count' => 0
            ];
        }
        return QuizResult::getDetailedStatsByPhone($this->phone);
    }

    // Scope للعملاء حسب النتيجة (ديناميكي)
    public function scopeWithHighScore($query, $minScore = 70)
    {
        return $query->whereHas('quizResults', function ($q) use ($minScore) {
            $q->havingRaw('SUM(user_marks) >= ?', [$minScore]);
        });
    }

    public function scopeOrderByScore($query, $direction = 'desc')
    {
        // للترتيب حسب Score، نستخدم subquery
        return $query->leftJoin('quiz_results', 'customers.phone', '=', 'quiz_results.phone')
            ->selectRaw('customers.*, SUM(quiz_results.user_marks) as calculated_score')
            ->groupBy('customers.id')
            ->orderBy('calculated_score', $direction);
    }
}