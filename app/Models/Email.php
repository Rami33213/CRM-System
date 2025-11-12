<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'email_type',
        'from_email',
        'to_email',
        'cc_email',
        'bcc_email',
        'subject',
        'body',
        'attachments',
        'status',
        'priority',
        'created_by',
        'sent_at',
        'read_at'
    ];

    protected $casts = [
        'attachments' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}