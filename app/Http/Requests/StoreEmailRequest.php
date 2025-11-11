<?php

// app/Http/Requests/StoreEmailRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailRequest extends FormRequest
{
    public function authorize()
    {
        // adjust authorization as needed
        return auth()->check();
    }

    public function rules()
    {
        return [
            'receiver_id' => ['required','integer','exists:users,id'],
            'subject' => ['nullable','string','max:255'],
            'body' => ['required','string'],
            'attachments' => ['nullable','array'],
            'attachments.*.name' => ['required_with:attachments','string'],
            'attachments.*.url' => ['required_with:attachments','url'],
            'priority' => ['nullable','in:low,normal,high'],
            'scheduled_at' => ['nullable','date'],
        ];
    }
}
