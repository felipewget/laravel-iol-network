<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $casts = [
        'transaction' => 'array'
    ];

    protected $fillable = [
        'index_id',
        'type',
        'transaction',
        'created_at',
        'uid',
        'hash',
    ];

    public $timestamps = false;

    public function block(): BelongsTo
    {
        return $this->BelongsTo(Block::class);
    }
}
