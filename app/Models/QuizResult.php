<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizResult extends Model
{
    protected $fillable = [
        'survey_id',
        'email',
        'phone',
        'user_marks',
        'total_marks',
        'correct_answers',
        'wrong_answers'
    ];

    protected $casts = [
        'user_marks' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'correct_answers' => 'integer',
        'wrong_answers' => 'integer'
    ];

    // علاقة مع Customer عن طريق phone
    public function customer()
    {
        return $this->hasOne(Customer::class, 'phone', 'phone');
    }

    // حساب النسبة المئوية
    public function getPercentageAttribute(): float
    {
        if ($this->total_marks == 0) return 0;
        return round(($this->user_marks / $this->total_marks) * 100, 2);
    }

    // حساب الدرجة (Grade)
    public function getGradeAttribute(): string
    {
        $percentage = $this->percentage;
        
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 85) return 'A';
        if ($percentage >= 80) return 'B+';
        if ($percentage >= 75) return 'B';
        if ($percentage >= 70) return 'C+';
        if ($percentage >= 65) return 'C';
        if ($percentage >= 60) return 'D';
        
        return 'F';
    }
}