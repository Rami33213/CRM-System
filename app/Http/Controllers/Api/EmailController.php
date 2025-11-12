<?php

namespace App\Http\Controllers\Api;

use App\Models\Email;
use App\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    /**
     * Get all emails for a customer
     */
    public function getByCustomer($customerId)
    {
        Customer::findOrFail($customerId);

        $emails = Email::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $emails
        ]);
    }

    /**
     * Store a new email
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'email_type' => 'required|in:incoming,outgoing',
            'from_email' => 'required|email',
            'to_email' => 'required|email',
            'cc_email' => 'nullable|email',
            'bcc_email' => 'nullable|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'priority' => 'nullable|in:low,normal,high'
        ]);

        $validated['created_by'] = auth()->id() ?? 1;
        $validated['status'] = $validated['email_type'] === 'outgoing' ? 'sent' : 'received';

        if ($validated['email_type'] === 'outgoing') {
            $validated['sent_at'] = now();
        }

        $email = Email::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Email sent successfully',
            'data' => $email
        ], 201);
    }

    /**
     * Mark email as read
     */
    public function markAsRead($id)
    {
        $email = Email::findOrFail($id);
        $email->update([
            'status' => 'read',
            'read_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email marked as read',
            'data' => $email
        ]);
    }

    /**
     * Delete email
     */
    public function destroy($id)
    {
        Email::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email deleted successfully'
        ]);
    }
}