<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialStatement extends Model
{
    use HasFactory;
    protected $primaryKey = 'soa_id';
    protected $fillable = [
        'LRN',
        'filename',
        'date_uploaded'
    ];
}
