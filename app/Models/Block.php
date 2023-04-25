<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends Model
{
    use HasFactory;

    protected $fillable = [
        'proof_of_work',
        'previous_hash',
        'index',
    ];

    public $timestamps = false;

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'index_id', 'index');
    }
}
