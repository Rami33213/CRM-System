<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Http\Controllers\Controller;  // ADD THIS LINE
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of all customers
     */
    public function index()
    {
        $customers = Customer::with(['segment','employee', 'progress'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'pagination' => [
                'total' => $customers->total(),
                'per_page' => $customers->perPage(),
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
            ]
        ]);
    }

    /**
     * Display the specified customer
     */
    public function show($id)
    {
        $customer = Customer::with(['segment', 'employee','progress'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'customer_segment_id' => 'required|exists:customer_segments,id'
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => $customer->load(['segment', 'progress'])
        ], 201);
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:customers,email,' . $id,
            'phone' => 'string|max:20',
            'address' => 'string',
            'customer_segment_id' => 'exists:customer_segments,id'
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer->load(['segment', 'progress'])
        ]);
    }

    /**
     * Delete the specified customer
     */
    public function destroy($id)
    {
        Customer::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    }
}