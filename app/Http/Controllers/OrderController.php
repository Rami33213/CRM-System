<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items', 'creator', 'assignedUser']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        // Search by order number
        if ($request->has('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        $orders = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'source' => 'required|in:whatsapp,website,phone,email,facebook,instagram,direct',
            'status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled,on_hold',
            'payment_status' => 'nullable|in:unpaid,partially_paid,paid,refunded',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'customer_requirements' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            
            // Order items
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|string',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.specifications' => 'nullable|string',
            'items.*.estimated_hours' => 'nullable|integer|min:0',
            'items.*.deliverables' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create order
            $order = Order::create([
                'customer_id' => $request->customer_id,
                'source' => $request->source,
                'status' => $request->status ?? 'pending',
                'payment_status' => $request->payment_status ?? 'unpaid',
                'tax_rate' => $request->tax_rate ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'expected_delivery_date' => $request->expected_delivery_date,
                'notes' => $request->notes,
                'customer_requirements' => $request->customer_requirements,
                'internal_notes' => $request->internal_notes,
                'created_by' => auth()->id(),
                'assigned_to' => $request->assigned_to,
                'order_date' => now(),
                'total' => 0 // سيتم حسابه تلقائياً
            ]);

            // Create order items
            foreach ($request->items as $itemData) {
                $order->items()->create($itemData);
            }

            // Calculate totals automatically
            $order->calculateTotals();

            DB::commit();

            $order->load(['customer', 'items', 'creator', 'assignedUser']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order
     */
    public function show($id)
    {
        $order = Order::with(['customer', 'items', 'creator', 'assignedUser'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update the specified order
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'source' => 'nullable|in:whatsapp,website,phone,email,facebook,instagram,direct',
            'status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled,on_hold',
            'payment_status' => 'nullable|in:unpaid,partially_paid,paid,refunded',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'expected_delivery_date' => 'nullable|date',
            'actual_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'customer_requirements' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order->update($request->only([
                'customer_id', 'source', 'status', 'payment_status',
                'tax_rate', 'discount_amount', 'expected_delivery_date',
                'actual_delivery_date', 'notes', 'customer_requirements',
                'internal_notes', 'assigned_to'
            ]));

            // Recalculate if tax or discount changed
            if ($request->has('tax_rate') || $request->has('discount_amount')) {
                $order->calculateTotals();
            }

            $order->load(['customer', 'items', 'creator', 'assignedUser']);

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified order
     */
    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        try {
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled,on_hold',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order->status = $request->status;
        
        if ($request->status === 'completed' && !$order->actual_delivery_date) {
            $order->actual_delivery_date = now();
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:unpaid,partially_paid,paid,refunded',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order->payment_status = $request->payment_status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully',
            'data' => $order
        ]);
    }

    /**
     * Get order statistics
     */
    public function statistics()
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'in_progress_orders' => Order::where('status', 'in_progress')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            
            'unpaid_orders' => Order::where('payment_status', 'unpaid')->count(),
            'paid_orders' => Order::where('payment_status', 'paid')->count(),
            
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total'),
            'pending_revenue' => Order::where('payment_status', 'unpaid')->sum('total'),
            
            'orders_this_month' => Order::whereMonth('order_date', now()->month)
                                        ->whereYear('order_date', now()->year)
                                        ->count(),
            'revenue_this_month' => Order::whereMonth('order_date', now()->month)
                                         ->whereYear('order_date', now()->year)
                                         ->where('payment_status', 'paid')
                                         ->sum('total'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}