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
        $query = Customer::with(['segment', 'quizResult']);

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

        // 3. التصفية حسب النتيجة (Score)
        if ($request->has('min_score')) {
            $query->where('score', '>=', $request->min_score);
        }

        if ($request->has('max_score')) {
            $query->where('score', '<=', $request->max_score);
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
        
        // التأكد من أن العمود موجود
        $allowedSortColumns = ['id', 'name', 'email', 'phone', 'score', 'created_at'];
        if (in_array($sortColumn, $allowedSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        // 6. عدد النتائج الكلي قبل الـ Pagination
        $totalRecords = $query->count();

        // 7. Pagination
        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);
        
        $customers = $query->paginate($perPage);

        // 8. إضافة بيانات إضافية لكل عميل
        $data = $customers->getCollection()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'score' => $customer->score,
                'segment' => $customer->segment ? [
                    'id' => $customer->segment->id,
                    'name' => $customer->segment->name
                ] : null,
                'quiz_result' => $customer->quizResult ? [
                    'user_marks' => $customer->quizResult->user_marks,
                    'total_marks' => $customer->quizResult->total_marks,
                    'percentage' => $customer->quizResult->percentage,
                    'grade' => $customer->quizResult->grade,
                    'correct_answers' => $customer->quizResult->correct_answers,
                    'wrong_answers' => $customer->quizResult->wrong_answers
                ] : null,
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
                'min_score' => $request->min_score,
                'max_score' => $request->max_score,
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
            'quizResult',
            'orders.items.service',
            'orders' => function ($query) {
                $query->latest();
            }
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'score' => $customer->score,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at
                ],
                'segment' => $customer->segment,
                'quiz_result' => $customer->quizResult ? [
                    'id' => $customer->quizResult->id,
                    'survey_id' => $customer->quizResult->survey_id,
                    'user_marks' => $customer->quizResult->user_marks,
                    'total_marks' => $customer->quizResult->total_marks,
                    'percentage' => $customer->quizResult->percentage,
                    'grade' => $customer->quizResult->grade,
                    'correct_answers' => $customer->quizResult->correct_answers,
                    'wrong_answers' => $customer->quizResult->wrong_answers,
                    'created_at' => $customer->quizResult->created_at
                ] : null,
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
     */
    public function syncScoreFromQuiz($id)
    {
        $customer = Customer::findOrFail($id);
        
        // البحث عن نتيجة الاختبار بنفس رقم الهاتف
        $quizResult = QuizResult::where('phone', $customer->phone)
            ->latest()
            ->first();

        if (!$quizResult) {
            return response()->json([
                'success' => false,
                'message' => 'No quiz result found for this customer'
            ], 404);
        }

        // تحديث النتيجة
        $customer->score = $quizResult->percentage;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Score synced successfully',
            'data' => [
                'customer_id' => $customer->id,
                'score' => $customer->score,
                'quiz_percentage' => $quizResult->percentage,
                'grade' => $quizResult->grade
            ]
        ]);
    }

    /**
     * Sync All Customers Scores from Quiz Results
     */
    public function syncAllScores()
    {
        $updated = 0;
        $customers = Customer::whereNotNull('phone')->get();

        foreach ($customers as $customer) {
            $quizResult = QuizResult::where('phone', $customer->phone)
                ->latest()
                ->first();

            if ($quizResult) {
                $customer->score = $quizResult->percentage;
                $customer->save();
                $updated++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Synced scores for {$updated} customers",
            'data' => [
                'total_customers' => $customers->count(),
                'updated_customers' => $updated
            ]
        ]);
    }

    /**
     * Get Statistics for Dashboard
     */
    public function statistics()
    {
        $stats = [
            'total_customers' => Customer::count(),
            'customers_with_score' => Customer::whereNotNull('score')->count(),
            'average_score' => Customer::whereNotNull('score')->avg('score'),
            'highest_score' => Customer::whereNotNull('score')->max('score'),
            'lowest_score' => Customer::whereNotNull('score')->min('score'),
            'total_revenue' => Customer::sum(DB::raw('(SELECT SUM(total) FROM orders WHERE orders.customer_id = customers.id)')),
            'score_distribution' => [
                'excellent' => Customer::where('score', '>=', 90)->count(),  // A+
                'very_good' => Customer::whereBetween('score', [80, 89])->count(), // A, B+
                'good' => Customer::whereBetween('score', [70, 79])->count(), // B, C+
                'average' => Customer::whereBetween('score', [60, 69])->count(), // C, D
                'poor' => Customer::where('score', '<', 60)->count() // F
            ],
            'top_customers' => Customer::with('segment')
                ->orderByScore('desc')
                ->limit(10)
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'score' => $customer->score,
                        'total_spent' => $customer->total_spent,
                        'segment' => $customer->segment?->name
                    ];
                })
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}