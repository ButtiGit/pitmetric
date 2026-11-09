<?php

namespace App\Services;

use App\Models\StudioSiteMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StudioSiteTraffic
{
    public function record(string $kind, string $subject): void
    {
        $subject = Str::limit($subject, 255, '');
        $day = now('Europe/Rome')->toDateString();
        $table = (new StudioSiteMetric)->getTable();
        $key = ['day' => $day, 'kind' => $kind, 'subject' => $subject];

        DB::table($table)->insertOrIgnore($key + [
            'value' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table($table)->where($key)->increment('value', 1, ['updated_at' => now()]);
    }
}
