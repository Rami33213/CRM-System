<?php
// app/Models/CustomerProgress.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProgress extends Model
{
    protected $table = 'customer_progress';
    
    protected $fillable = [
        'customer_id',
        'customer_status',
        'action',
        'description',
        'action_date',
        'completed_at',
        'priority',
        'created_by'
    ];

    protected $casts = [
        'action_date' => 'date',
        'completed_at' => 'datetime'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}