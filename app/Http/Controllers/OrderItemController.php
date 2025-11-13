<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderItemController extends Controller
{
    /**
     * Get all items for a specific order
     */
    public function index($orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $items = $order->items;

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Add new item to order
     */
    public function store(Request $request, $orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string',
            'description' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'specifications' => 'nullable|string',
            'estimated_hours' => 'nullable|integer|min:0',
            'deliverables' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,under_review,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $item = $order->items()->create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Order item added successfully',
                'data' => $item
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add order item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific order item
     */
    public function show($orderId, $itemId)
    {
        $item = OrderItem::where('order_id', $orderId)->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    /**
     * Update order item
     */
    public function update(Request $request, $orderId, $itemId)
    {
        $item = OrderItem::where('order_id', $orderId)->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_type' => 'nullable|string',
            'description' => 'nullable|string',
            'quantity' => 'nullable|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'specifications' => 'nullable|string',
            'estimated_hours' => 'nullable|integer|min:0',
            'deliverables' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,under_review,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $item->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Order item updated successfully',
                'data' => $item
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete order item
     */
    public function destroy($orderId, $itemId)
    {
        $item = OrderItem::where('order_id', $orderId)->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found'
            ], 404);
        }

        try {
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order item deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update item progress
     */
    public function updateProgress(Request $request, $orderId, $itemId)
    {
        $item = OrderItem::where('order_id', $orderId)->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'progress_percentage' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $item->updateProgress($request->progress_percentage);

            return response()->json([
                'success' => true,
                'message' => 'Progress updated successfully',
                'data' => $item
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update item status
     */
    public function updateStatus(Request $request, $orderId, $itemId)
    {
        $item = OrderItem::where('order_id', $orderId)->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,under_review,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $item->status = $request->status;
            
            if ($request->status === 'completed') {
                $item->progress_percentage = 100;
                $item->end_date = now();
            } elseif ($request->status === 'in_progress' && !$item->start_date) {
                $item->start_date = now();
            }
            
            $item->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $item
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}