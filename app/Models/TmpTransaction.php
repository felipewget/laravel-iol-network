<?php

namespace App\Models;

use App\Observers\TmpTransactionObserver;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TmpTransaction extends Model
{
    use HasFactory;

    protected $casts = [
        'transaction' => 'array'
    ];

    protected $fillable = [
        'uid',
        'type',
        'transaction',
        'tip',
        'created_at'
    ];

    public $timestamps = false;
}
