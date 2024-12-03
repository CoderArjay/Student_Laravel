<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory;
    protected $primaryKey = 'payment_id';
    protected $fillable = [
        'LRN',
        'OR_number',
        'amount_paid',
        'proof_payment',
        'description',
        'date_of_payment'
    ];

    protected $attributes = [
        'date_of_payment' => null, // Initialize as null
    ];

    // Automatically set date_of_payment to now when creating a new record
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (is_null($payment->date_of_payment)) {
                $payment->date_of_payment = Carbon::now(); // Set to current date and time
            }
        });
    }
}
