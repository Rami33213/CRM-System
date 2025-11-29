<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    /**
     * حساب النتيجة الإجمالية لعميل حسب رقم الهاتف
     * يجمع كل user_marks من جميع الأسطر
     */
    public static function calculateTotalScoreByPhone(string $phone): float
    {
        return self::where('phone', $phone)
            ->sum('user_marks');
    }

    /**
     * حساب النسبة المئوية للعميل
     * إجمالي user_marks من 100
     */
    public static function calculatePercentageByPhone(string $phone): float
    {
        $totalUserMarks = self::where('phone', $phone)
            ->sum('user_marks');
        
        // النتيجة مباشرة من 100
        return round($totalUserMarks, 2);
    }

    /**
     * الحصول على جميع نتائج الاختبار لعميل
     */
    public static function getResultsByPhone(string $phone)
    {
        return self::where('phone', $phone)->get();
    }

    /**
     * عدد الأسئلة التي أجاب عليها العميل
     */
    public static function getQuestionsCountByPhone(string $phone): int
    {
        return self::where('phone', $phone)->count();
    }

    /**
     * حساب الدرجة (Grade) بناءً على النتيجة
     */
    public static function getGradeByPhone(string $phone): string
    {
        $score = self::calculatePercentageByPhone($phone);
        
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'B+';
        if ($score >= 75) return 'B';
        if ($score >= 70) return 'C+';
        if ($score >= 65) return 'C';
        if ($score >= 60) return 'D';
        
        return 'F';
    }

    /**
     * إحصائيات مفصلة لعميل
     */
    public static function getDetailedStatsByPhone(string $phone): array
    {
        $results = self::where('phone', $phone)->get();
        
        if ($results->isEmpty()) {
            return [
                'total_score' => 0,
                'percentage' => 0,
                'grade' => 'N/A',
                'questions_count' => 0,
                'total_correct_answers' => 0,
                'total_wrong_answers' => 0,
                'questions_details' => []
            ];
        }

        return [
            'total_score' => round($results->sum('user_marks'), 2),
            'percentage' => round($results->sum('user_marks'), 2),
            'grade' => self::getGradeByPhone($phone),
            'questions_count' => $results->count(),
            'total_correct_answers' => $results->sum('correct_answers'),
            'total_wrong_answers' => $results->sum('wrong_answers'),
            'questions_details' => $results->map(function ($result) {
                return [
                    'survey_id' => $result->survey_id,
                    'user_marks' => $result->user_marks,
                    'total_marks' => $result->total_marks,
                    'correct_answers' => $result->correct_answers,
                    'wrong_answers' => $result->wrong_answers,
                    'answered_at' => $result->created_at
                ];
            })->toArray()
        ];
    }
}