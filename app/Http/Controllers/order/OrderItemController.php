<?php

namespace App\Http\Controllers\order;
use App\Http\Controllers\Controller;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function index($orderId)
    {
        $order = Order::findOrFail($orderId);
        $items = $order->items()->with('service')->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    public function store(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'customization_notes' => 'nullable|string',
            'specifications' => 'nullable|array',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled'
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $validated['service_id'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'discount' => $validated['discount'] ?? 0,
            'customization_notes' => $validated['customization_notes'] ?? null,
            'specifications' => $validated['specifications'] ?? null,
            'status' => $validated['status'] ?? 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order item added successfully',
            'data' => $item->load('service')
        ], 201);
    }

    public function update(Request $request, $orderId, $itemId)
    {
        $order = Order::findOrFail($orderId);
        $item = OrderItem::where('order_id', $order->id)->findOrFail($itemId);

        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'unit_price' => 'sometimes|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'customization_notes' => 'nullable|string',
            'specifications' => 'nullable|array',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled'
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Order item updated successfully',
            'data' => $item->load('service')
        ]);
    }

    public function destroy($orderId, $itemId)
    {
        $order = Order::findOrFail($orderId);
        $item = OrderItem::where('order_id', $order->id)->findOrFail($itemId);
        
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order item deleted successfully'
        ]);
    }

    public function updateStatus(Request $request, $orderId, $itemId)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled'
        ]);

        $order = Order::findOrFail($orderId);
        $item = OrderItem::where('order_id', $order->id)->findOrFail($itemId);
        
        $item->status = $validated['status'];
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Order item status updated successfully',
            'data' => $item
        ]);
    }
}