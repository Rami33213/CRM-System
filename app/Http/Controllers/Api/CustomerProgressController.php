<?php

namespace App\Http\Controllers\Api;

use App\Models\CustomerProgress;
use App\Models\Customer;
use App\Http\Controllers\Controller;  // ADD THIS LINE
use Illuminate\Http\Request;

class CustomerProgressController extends Controller
{
    /**
     * Get all progress records for a specific customer
     */
    public function getByCustomer($customerId)
    {
        // Verify customer exists
        Customer::findOrFail($customerId);

        $progress = CustomerProgress::where('customer_id', $customerId)
            ->orderBy('action_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $progress
        ]);
    }

    /**
     * Store a new progress record
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'customer_status' => 'required|in:prospect,negotiation,proposal_sent,deal_closed,on_hold',
            'action' => 'required|string|max:255',
            'description' => 'nullable|string',
            'action_date' => 'required|date',
            'priority' => 'required|in:low,medium,high'
        ]);

        // Add the authenticated user's ID
        $validated['created_by'] = auth()->id() ?? 1;

        $progress = CustomerProgress::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Progress record created successfully',
            'data' => $progress
        ], 201);
    }

    /**
     * Update a progress record
     */
    public function update(Request $request, $id)
    {
        $progress = CustomerProgress::findOrFail($id);

        $validated = $request->validate([
            'customer_status' => 'in:prospect,negotiation,proposal_sent,deal_closed,on_hold',
            'action' => 'string|max:255',
            'description' => 'nullable|string',
            'action_date' => 'date',
            'completed_at' => 'nullable|date',
            'priority' => 'in:low,medium,high'
        ]);

        $progress->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Progress record updated successfully',
            'data' => $progress
        ]);
    }

    /**
     * Delete a progress record
     */
    public function destroy($id)
    {
        CustomerProgress::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Progress record deleted successfully'
        ]);
    }
}