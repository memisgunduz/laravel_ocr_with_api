<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OcrJobBlock extends Model
{
    use HasFactory;
    protected $fillable = [
        'block_id',
    ];
}
