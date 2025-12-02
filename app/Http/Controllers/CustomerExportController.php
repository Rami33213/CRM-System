<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CustomerExportController extends Controller
{
    
    public function exportCSV(Request $request)
    {
        $customers = $this->getFilteredCustomers($request);

        $filename = 'customers_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($customers) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM للدعم العربي في Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header Row
            fputcsv($file, [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Address',
                'Quiz Score',
                'Quiz Grade',
                'Questions Answered',
                'Segment',
                'Employee',
                'Total Orders',
                'Total Spent',
                'Unpaid Amount',
                'Created At'
            ]);

            // Data Rows
            foreach ($customers as $customer) {
                $quizStats = $customer->quiz_stats;
                
                fputcsv($file, [
                    $customer->id,
                    $customer->name,
                    $customer->email,
                    $customer->phone,
                    $customer->address,
                    $quizStats['total_score'],
                    $quizStats['grade'],
                    $quizStats['questions_count'],
                    $customer->segment?->name ?? 'N/A',
                    $customer->employee?->name ?? 'N/A',
                    $customer->total_orders,
                    $customer->total_spent,
                    $customer->unpaid_amount,
                    $customer->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export Customers to Excel (XLSX)
     */
    public function exportExcel(Request $request)
    {
        $customers = $this->getFilteredCustomers($request);

        $filename = 'customers_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Generate Excel-like XML format
        $content = $this->generateExcelXML($customers);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Customers to JSON
     */
    public function exportJSON(Request $request)
    {
        $customers = $this->getFilteredCustomers($request);

        $data = $customers->map(function ($customer) {
            $quizStats = $customer->quiz_stats;
            
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'quiz_results' => [
                    'total_score' => $quizStats['total_score'],
                    'percentage' => $quizStats['percentage'],
                    'grade' => $quizStats['grade'],
                    'questions_count' => $quizStats['questions_count'],
                    'total_correct_answers' => $quizStats['total_correct_answers'] ?? 0,
                    'total_wrong_answers' => $quizStats['total_wrong_answers'] ?? 0
                ],
                'segment' => $customer->segment?->name,
                'employee' => $customer->employee ? [
                    'id' => $customer->employee->id,
                    'name' => $customer->employee->name
                ] : null,
                'statistics' => [
                    'total_orders' => $customer->total_orders,
                    'total_spent' => $customer->total_spent,
                    'unpaid_amount' => $customer->unpaid_amount
                ],
                'created_at' => $customer->created_at->format('Y-m-d H:i:s')
            ];
        });

        $filename = 'customers_' . date('Y-m-d_H-i-s') . '.json';

        return response()->json([
            'success' => true,
            'data' => $data,
            'export_date' => now()->format('Y-m-d H:i:s'),
            'total_records' => $data->count()
        ])->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export Single Customer Details with Orders
     */
    public function exportCustomerDetails($id)
    {
        $customer = Customer::with([
            'segment',
            'employee',
            'quizResults',
            'orders.items.service'
        ])->findOrFail($id);

        $quizStats = $customer->quiz_stats;

        $filename = 'customer_' . $customer->id . '_' . date('Y-m-d_H-i-s') . '.json';

        $data = [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'segment' => $customer->segment?->name,
                'employee' => $customer->employee ? [
                    'id' => $customer->employee->id,
                    'name' => $customer->employee->name
                ] : null
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
                'total_spent' => $customer->total_spent,
                'unpaid_amount' => $customer->unpaid_amount
            ],
            'orders' => $customer->orders->map(function ($order) {
                return [
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'total' => $order->total,
                    'paid_amount' => $order->paid_amount,
                    'source' => $order->source,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'service' => $item->service->name,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total' => $item->total
                        ];
                    }),
                    'created_at' => $order->created_at->format('Y-m-d H:i:s')
                ];
            }),
            'export_date' => now()->format('Y-m-d H:i:s')
        ];

        return response()->json($data)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Get Filtered Customers (helper method)
     */
    private function getFilteredCustomers(Request $request)
    {
        $query = Customer::with(['segment', 'employee', 'quizResults']);

        // Apply same filters as DataTable
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('segment_id')) {
            $query->where('customer_segment_id', $request->segment_id);
        }

        if ($request->has('employee_id')) {
            if ($request->employee_id === 'unassigned') {
                $query->whereNull('employee_id');
            } else {
                $query->where('employee_id', $request->employee_id);
            }
        }

        // Sorting
        $sortColumn = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        if ($sortColumn === 'score') {
            // ترتيب حسب Score
            $query->leftJoin('quiz_results', 'customers.phone', '=', 'quiz_results.phone')
                ->selectRaw('customers.*, SUM(quiz_results.user_marks) as calculated_score')
                ->groupBy('customers.id')
                ->orderBy('calculated_score', $sortDirection);
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        return $query->get();
    }

    /**
     * Generate Excel XML format
     */
    private function generateExcelXML($customers)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= '<Worksheet ss:Name="Customers"><Table>' . "\n";
        
        // Header
        $xml .= '<Row>';
        $xml .= '<Cell><Data ss:Type="String">ID</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">Name</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">Email</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">Phone</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">Score</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">Segment</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">Total Orders</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">Total Spent</Data></Cell>';
        $xml .= '</Row>' . "\n";

        // Data
        foreach ($customers as $customer) {
            $quizStats = $customer->quiz_stats;
            
            $xml .= '<Row>';
            $xml .= '<Cell><Data ss:Type="Number">' . $customer->id . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($customer->name) . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($customer->email) . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($customer->phone) . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="Number">' . $quizStats['total_score'] . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . $quizStats['grade'] . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($customer->segment?->name ?? 'N/A') . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="Number">' . $customer->total_orders . '</Data></Cell>';
            $xml .= '<Cell><Data ss:Type="Number">' . $customer->total_spent . '</Data></Cell>';
            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table></Worksheet></Workbook>';
        
        return $xml;
    }
}