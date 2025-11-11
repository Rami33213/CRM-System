<?php
// app/Models/CustomerSegment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSegment extends Model
{
    use HasFactory;
    // Overrides table name if it's not 'customer_segments'
    protected $table = 'customer_segments'; 

    // Connects CustomerSegment -> Customer (one segment can have MANY customers)
    public function customers()
    {
        return $this->hasMany(Customer::class, 'customer_segment_id');
    }
}