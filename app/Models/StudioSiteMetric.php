<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StudioSiteMetric extends Model
{
    protected $fillable = ['day', 'kind', 'subject', 'value'];

    protected function casts(): array
    {
        return ['day' => 'date', 'value' => 'integer'];
    }
}
