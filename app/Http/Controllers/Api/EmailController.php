<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Email;

class EmailController extends Controller
{
     // ✅ عرض كل الرسائل
    public function index()
    {
        return response()->json(Email::with(['sender', 'receiver'])->get());
    }

    // ✅ عرض رسالة محددة
    public function show($id)
    {
        $email = Email::with(['sender', 'receiver'])->find($id);
        if (!$email) {
            return response()->json(['message' => 'Email not found'], 404);
        }
        return response()->json($email);
    }

    // ✅ إرسال رسالة جديدة
    public function store(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'priority' => 'nullable|in:low,normal,high',
        ]);

        $email = Email::create([
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'subject' => $request->subject,
            'body' => $request->body,
            'priority' => $request->priority ?? 'normal',
            'is_read' => 0, // افتراضي غير مقروء
            'flag' => 0,
        ]);

        return response()->json([
            'message' => 'Email sent successfully!',
            'data' => $email
        ], 201);
    }

    public function markAsRead($id)
    {
        $email = Email::find($id);
        if (!$email) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        $email->is_read = 1;
        $email->save();

        return response()->json(['message' => 'Email marked as read successfully']);
    }

    // ✅ حذف رسالة
    public function destroy($id)
    {
        $email = Email::find($id);
        if (!$email) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        $email->delete();
        return response()->json(['message' => 'Email deleted successfully']);
    }
}
