<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\QuizResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerDatatableController extends Controller
{
    /**
     * Get Customers DataTable with Server-Side Processing
     */
    public function index(Request $request)
    {
        // Query أساسي
        $query = Customer::with(['segment', 'employee', 'quizResults']);

        // 1. البحث (Search)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%");
            });
        }

        // 2. التصفية حسب الـ Segment
        if ($request->has('segment_id') && !empty($request->segment_id)) {
            $query->where('customer_segment_id', $request->segment_id);
        }

        // 3. التصفية حسب الموظف
        if ($request->has('employee_id')) {
            if ($request->employee_id === 'unassigned') {
                $query->whereNull('employee_id');
            } else {
                $query->where('employee_id', $request->employee_id);
            }
        }

        // 4. التصفية حسب التاريخ
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 5. الترتيب (Sorting)
        $sortColumn = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // معالجة خاصة للترتيب حسب Score
        if ($sortColumn === 'score') {
            // استخدام subquery للترتيب حسب مجموع user_marks
            $query->leftJoin('quiz_results', 'customers.phone', '=', 'quiz_results.phone')
                ->selectRaw('customers.*, SUM(quiz_results.user_marks) as calculated_score')
                ->groupBy('customers.id', 'customers.customer_segment_id', 'customers.employee_id', 
                         'customers.name', 'customers.email', 'customers.phone', 'customers.address', 
                         'customers.created_at', 'customers.updated_at')
                ->orderBy('calculated_score', $sortDirection);
        } else {
            // الترتيب العادي
            $allowedSortColumns = ['id', 'name', 'email', 'phone', 'created_at'];
            if (in_array($sortColumn, $allowedSortColumns)) {
                $query->orderBy($sortColumn, $sortDirection);
            }
        }

        // 6. Pagination
        $perPage = $request->get('per_page', 25);
        $customers = $query->paginate($perPage);

        // 7. إضافة بيانات إضافية لكل عميل
        $data = $customers->getCollection()->map(function ($customer) {
            // حساب Score ديناميكياً من quiz_results
            $quizStats = $customer->quiz_stats;

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'segment' => $customer->segment ? [
                    'id' => $customer->segment->id,
                    'name' => $customer->segment->name
                ] : null,
                'employee' => $customer->employee ? [
                    'id' => $customer->employee->id,
                    'name' => $customer->employee->name,
                    'position' => $customer->employee->position,
                    'status' => 'assigned'
                ] : [
                    'id' => null,
                    'name' => null,
                    'position' => null,
                    'status' => 'unassigned'
                ],
                'quiz_results' => [
                    'total_score' => $quizStats['total_score'],
                    'percentage' => $quizStats['percentage'],
                    'grade' => $quizStats['grade'],
                    'questions_count' => $quizStats['questions_count'],
                    'total_correct_answers' => $quizStats['total_correct_answers'] ?? 0,
                    'total_wrong_answers' => $quizStats['total_wrong_answers'] ?? 0
                ],
                'statistics' => [
                    'total_orders' => $customer->total_orders,
                    'total_spent' => $customer->total_spent,
                    'pending_orders' => $customer->pending_orders,
                    'unpaid_amount' => $customer->unpaid_amount
                ],
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'last_page' => $customers->lastPage(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem()
            ],
            'filters' => [
                'search' => $request->search,
                'segment_id' => $request->segment_id,
                'employee_id' => $request->employee_id,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'sort_by' => $sortColumn,
                'sort_direction' => $sortDirection
            ]
        ]);
    }

    /**
     * View Customer Details (كل التفاصيل + Orders)
     */
    public function show($id)
    {
        $customer = Customer::with([
            'segment',
            'employee',
            'quizResults', // تغيير من quizResult إلى quizResults
            'orders.items.service',
            'orders' => function ($query) {
                $query->latest();
            }
        ])->findOrFail($id);

        // حساب إحصائيات الاختبار
        $quizStats = $customer->quiz_stats;

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at
                ],
                'segment' => $customer->segment,
                'employee' => $customer->employee ? [
                    'id' => $customer->employee->id,
                    'name' => $customer->employee->name,
                    'position' => $customer->employee->position,
                    'department' => $customer->employee->department,
                    'status' => 'assigned'
                ] : [
                    'id' => null,
                    'name' => null,
                    'position' => null,
                    'department' => null,
                    'status' => 'unassigned'
                ],
                'quiz_results' => [
                    'total_score' => $quizStats['total_score'],
                    'percentage' => $quizStats['percentage'],
                    'grade' => $quizStats['grade'],
                    'questions_count' => $quizStats['questions_count'],
                    'total_correct_answers' => $quizStats['total_correct_answers'] ?? 0,
                    'total_wrong_answers' => $quizStats['total_wrong_answers'] ?? 0,
                    'questions_details' => $quizStats['questions_details'] ?? []
                ],
                'statistics' => [
                    'total_orders' => $customer->total_orders,
                    'completed_orders' => $customer->completed_orders,
                    'pending_orders' => $customer->pending_orders,
                    'total_spent' => number_format($customer->total_spent, 2),
                    'unpaid_amount' => number_format($customer->unpaid_amount, 2)
                ],
                'orders' => $customer->orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'total' => $order->total,
                        'paid_amount' => $order->paid_amount,
                        'remaining_amount' => $order->remaining_amount,
                        'source' => $order->source,
                        'items_count' => $order->items->count(),
                        'items' => $order->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'service_name' => $item->service->name,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'total' => $item->total,
                                'status' => $item->status
                            ];
                        }),
                        'created_at' => $order->created_at,
                        'expected_delivery_date' => $order->expected_delivery_date
                    ];
                })
            ]
        ]);
    }

    /**
     * Update Customer Score from Quiz Result
     * ملاحظة: Score الآن يُحسب ديناميكياً، هذا الـ endpoint للمعلومات فقط
     */
    public function syncScoreFromQuiz($id)
    {
        $customer = Customer::findOrFail($id);
        
        if (!$customer->phone) {
            return response()->json([
                'success' => false,
                'message' => 'Customer does not have a phone number'
            ], 400);
        }

        // حساب النتيجة من quiz_results
        $quizStats = $customer->quiz_stats;

        if ($quizStats['questions_count'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No quiz results found for this customer'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Score calculated successfully',
            'data' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'phone' => $customer->phone,
                'quiz_results' => $quizStats
            ]
        ]);
    }

    /**
     * Sync All Customers Scores from Quiz Results
     * ملاحظة: Score الآن يُحسب ديناميكياً، هذا للمعلومات فقط
     */
    public function syncAllScores()
    {
        $customers = Customer::whereNotNull('phone')->get();
        $withResults = 0;
        $withoutResults = 0;

        $summary = [];

        foreach ($customers as $customer) {
            $quizStats = $customer->quiz_stats;
            
            if ($quizStats['questions_count'] > 0) {
                $withResults++;
                $summary[] = [
                    'customer_id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'score' => $quizStats['total_score'],
                    'grade' => $quizStats['grade']
                ];
            } else {
                $withoutResults++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Scores calculated for all customers',
            'data' => [
                'total_customers' => $customers->count(),
                'with_quiz_results' => $withResults,
                'without_quiz_results' => $withoutResults,
                'top_10_customers' => collect($summary)
                    ->sortByDesc('score')
                    ->take(10)
                    ->values()
            ]
        ]);
    }

    /**
     * Get Statistics for Dashboard
     */
    public function statistics()
    {
        // حساب الإحصائيات باستخدام Scores الديناميكية
        $customers = Customer::with('quizResults')->get();
        
        $customersWithScore = $customers->filter(function ($customer) {
            return $customer->quiz_questions_count > 0;
        });

        $scores = $customersWithScore->pluck('score')->filter();

        $stats = [
            'total_customers' => $customers->count(),
            'customers_with_quiz_results' => $customersWithScore->count(),
            'customers_without_quiz_results' => $customers->count() - $customersWithScore->count(),
            'average_score' => $scores->isNotEmpty() ? round($scores->avg(), 2) : 0,
            'highest_score' => $scores->isNotEmpty() ? $scores->max() : 0,
            'lowest_score' => $scores->isNotEmpty() ? $scores->min() : 0,
            'total_revenue' => $customers->sum(function ($customer) {
                return $customer->total_spent;
            }),
            'score_distribution' => [
                'excellent' => $customersWithScore->filter(fn($c) => $c->score >= 90)->count(),
                'very_good' => $customersWithScore->filter(fn($c) => $c->score >= 80 && $c->score < 90)->count(),
                'good' => $customersWithScore->filter(fn($c) => $c->score >= 70 && $c->score < 80)->count(),
                'average' => $customersWithScore->filter(fn($c) => $c->score >= 60 && $c->score < 70)->count(),
                'poor' => $customersWithScore->filter(fn($c) => $c->score < 60)->count()
            ],
            'top_customers' => $customersWithScore
                ->sortByDesc('score')
                ->take(10)
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'score' => $customer->score,
                        'grade' => $customer->quiz_grade,
                        'questions_answered' => $customer->quiz_questions_count,
                        'total_spent' => $customer->total_spent,
                        'segment' => $customer->segment?->name
                    ];
                })
                ->values()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}