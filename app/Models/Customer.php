<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Customer extends Model
{
    protected $fillable = [
        'customer_segment_id',
        'employee_id',
        'name',
        'email',
        'phone',
        'address'
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id');
    }

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

    /**
     * Many-to-Many relationship with Tags
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'customer_tag')
            ->withTimestamps();
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

    public function quizResults()
    {
        return $this->hasMany(QuizResult::class, 'phone', 'phone');
    }

    public function getScoreAttribute(): float
    {
        if (!$this->phone) return 0;
        return QuizResult::calculateTotalScoreByPhone($this->phone);
    }

    public function getQuizPercentageAttribute(): float
    {
        if (!$this->phone) return 0;
        return QuizResult::calculatePercentageByPhone($this->phone);
    }

    public function getQuizGradeAttribute(): string
    {
        if (!$this->phone) return 'N/A';
        return QuizResult::getGradeByPhone($this->phone);
    }

    public function getQuizQuestionsCountAttribute(): int
    {
        if (!$this->phone) return 0;
        return QuizResult::getQuestionsCountByPhone($this->phone);
    }

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

    public function scopeWithHighScore($query, $minScore = 70)
    {
        return $query->whereHas('quizResults', function ($q) use ($minScore) {
            $q->havingRaw('SUM(user_marks) >= ?', [$minScore]);
        });
    }

    public function scopeOrderByScore($query, $direction = 'desc')
    {
        return $query->leftJoin('quiz_results', 'customers.phone', '=', 'quiz_results.phone')
            ->selectRaw('customers.*, SUM(quiz_results.user_marks) as calculated_score')
            ->groupBy('customers.id')
            ->orderBy('calculated_score', $direction);
    }

    /**
     * Scope: Filter by tag
     */
    public function scopeByTag($query, int $tagId)
    {
        return $query->whereHas('tags', function ($q) use ($tagId) {
            $q->where('tags.id', $tagId);
        });
    }

    /**
     * Scope: Filter by multiple tags (any)
     */
    public function scopeByAnyTag($query, array $tagIds)
    {
        return $query->whereHas('tags', function ($q) use ($tagIds) {
            $q->whereIn('tags.id', $tagIds);
        });
    }

    /**
     * Scope: Filter by multiple tags (all)
     */
    public function scopeByAllTags($query, array $tagIds)
    {
        foreach ($tagIds as $tagId) {
            $query->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }
        return $query;
    }
}