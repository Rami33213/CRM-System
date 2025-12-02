<?php

namespace App\Http\Controllers\Api;

use App\Models\Email;
use App\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\CrmEmail;


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

    // قيم مبدئية
    $status = $validated['email_type'] === 'outgoing' ? 'sent' : 'received';
    $sentAt = null;

    if ($validated['email_type'] === 'outgoing') {
        try {
            $mail = Mail::to($validated['to_email']);

            if (!empty($validated['cc_email'])) {
                $mail->cc($validated['cc_email']);
            }

            if (!empty($validated['bcc_email'])) {
                $mail->bcc($validated['bcc_email']);
            }

            $mail->send(new CrmEmail(
                $validated['subject'],
                $validated['body']
            ));

            $sentAt = now();
            $status = 'sent';

        } catch (\Throwable $e) {
            \Log::error('SMTP email sending failed', [
                'error' => $e->getMessage(),
                'to' => $validated['to_email'],
            ]);

            // 🔴 رجعنا Error وما خزّنا شي بالداتابيس
            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال الإيميل عبر SMTP',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    $validated['status'] = $status;
    $validated['sent_at'] = $sentAt;

    $email = Email::create($validated);

    return response()->json([
        'success' => true,
        'message' => $status === 'sent'
            ? 'Email sent and stored successfully'
            : 'Email stored successfully',
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