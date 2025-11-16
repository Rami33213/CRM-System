<?php

namespace App\Http\Controllers\order;
use App\Http\Controllers\Controller;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items.service']);

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('source')) {
            $query->bySource($request->source);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        $orders = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled,on_hold',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'source' => 'required|in:whatsapp,email,phone,website,direct',
            'expected_delivery_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.customization_notes' => 'nullable|string',
            'items.*.specifications' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'status' => $validated['status'] ?? 'pending',
                'discount' => $validated['discount'] ?? 0,
                'tax' => $validated['tax'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'source' => $validated['source'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null
            ]);

            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $item['service_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'customization_notes' => $item['customization_notes'] ?? null,
                    'specifications' => $item['specifications'] ?? null
                ]);
            }

            $order->calculateTotals();
            $order->load(['customer', 'items.service']);

            DB::commit();

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

    public function show($id)
    {
        $order = Order::with(['customer', 'items.service'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled,on_hold',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'expected_delivery_date' => 'nullable|date',
            'actual_delivery_date' => 'nullable|date'
        ]);

        $order->update($validated);
        $order->calculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order->load(['customer', 'items.service'])
        ]);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled,on_hold'
        ]);

        $order = Order::findOrFail($id);
        $order->status = $validated['status'];

        if ($validated['status'] === 'completed' && !$order->actual_delivery_date) {
            $order->actual_delivery_date = now();
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    public function addPayment(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0'
        ]);

        $order = Order::findOrFail($id);
        $order->paid_amount += $validated['amount'];
        $order->calculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Payment added successfully',
            'data' => $order
        ]);
    }

    public function stats()
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::pending()->count(),
            'completed_orders' => Order::completed()->count(),
            'total_revenue' => Order::sum('total'),
            'unpaid_amount' => Order::unpaid()->sum('total'),
            'orders_by_source' => Order::select('source', DB::raw('count(*) as total'))
                ->groupBy('source')
                ->get(),
            'orders_by_status' => Order::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}