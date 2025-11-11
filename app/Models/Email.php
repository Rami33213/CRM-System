<?php

// app/Models/Email.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Email extends Model
{
    use SoftDeletes;

    protected $table = 'emails';

    protected $fillable = [
        'uuid',
        'sender_id',
        'receiver_id',
        'subject',
        'body',
        'attachments',
        'thread_id',
        'is_flagged',
        'is_read',
        'read_at',
        'scheduled_at',
        'sent_at',
        'priority',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_flagged' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    // Boot: generate uuid if not provided
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Relations (assumes User model exists)
    public function sender()
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(\App\Models\User::class, 'receiver_id');
    }

    public function thread()
    {
        return $this->belongsTo(self::class, 'thread_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'thread_id');
    }

    // Helper to mark read
    public function markAsRead()
    {
        $this->is_read = true;
        $this->read_at = now();
        $this->save();
    }

    public function markAsUnread()
    {
        $this->is_read = false;
        $this->read_at = null;
        $this->save();
    }

    public function toggleFlag()
    {
        $this->is_flagged = !$this->is_flagged;
        $this->save();
    }
}
