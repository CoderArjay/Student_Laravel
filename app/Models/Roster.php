<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roster extends Model
{
    use HasFactory;
    protected $primaryKey = 'roster_id';
    protected $fillable = [
        'LRN',
        'class_id'
    ];
}
