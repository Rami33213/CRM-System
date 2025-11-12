<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = [
        'customer_segment_id',
        'name',
        'email',
        'phone',
        'address'
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CustomerProgress::class);
    }
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }
}