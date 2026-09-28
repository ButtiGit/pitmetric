<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingSuppression extends Model
{
    use HasFactory;

    protected $fillable = ['email_hash'];
}
