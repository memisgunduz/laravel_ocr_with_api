<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GetData extends Model
{
    use HasFactory;
    protected $fillable = [
        'json_data',
    ];
}
