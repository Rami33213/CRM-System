<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use App\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Get all messages for a customer
     */
    public function getByCustomer($customerId)
    {
        Customer::findOrFail($customerId);

        $messages = Message::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Store a new message
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'message_type' => 'required|in:incoming,outgoing',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'sender_name' => 'nullable|string|max:255',
            'receiver_name' => 'nullable|string|max:255'
        ]);

        $validated['sender_type'] = 'system';
        $message = Message::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Message created successfully',
            'data' => $message
        ], 201);
    }

    /**
     * Mark message as read
     */
    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);
        $message->update(['status' => 'read']);

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
            'data' => $message
        ]);
    }

    /**
     * Delete message
     */
    public function destroy($id)
    {
        Message::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }
}