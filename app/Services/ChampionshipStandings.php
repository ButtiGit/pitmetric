<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\ChampionshipResult;

class ChampionshipStandings
{
    public function points(Championship $championship, ChampionshipResult $result): int
    {
        $score = (int) $result->adjustment;
        if ($result->status === 'classified') {
            $score += (int) ($championship->points[$result->position] ?? 0);
            $score += $result->pole ? $championship->pole_bonus : 0;
            $score += $result->fastest_lap ? $championship->fastest_lap_bonus : 0;
        }

        return $score;
    }

    /** @return list<array<string, mixed>> */
    public function table(Championship $championship): array
    {
        $championship->loadMissing(['participants.driver', 'rounds.results']);
        $rows = [];
        foreach ($championship->participants as $participant) {
            $rows[$participant->id] = ['participant' => $participant, 'points' => 0, 'starts' => 0, 'finishes' => [], 'positions' => [], 'rounds' => []];
        }
        foreach ($championship->rounds as $round) {
            if ($round->status === 'cancelled') {
                continue;
            }
            foreach ($round->results as $result) {
                if (! isset($rows[$result->participant_id])) {
                    continue;
                }
                $row = &$rows[$result->participant_id];
                $points = $this->points($championship, $result);
                $row['points'] += $points;
                $row['starts'] += in_array($result->status, ['classified', 'dnf'], true) ? 1 : 0;
                $row['rounds'][$round->id] = $points;
                if ($result->status === 'classified' && $result->position !== null) {
                    $row['positions'][] = $result->position;
                }
                unset($row);
            }
        }
        foreach ($rows as &$row) {
            $row['finishes'] = array_count_values($row['positions']);
            unset($row['positions']);
        }
        unset($row);
        $compare = static function (array $a, array $b): int {
            $points = $b['points'] <=> $a['points'];
            if ($points !== 0) {
                return $points;
            }
            for ($position = 1; $position <= 500; $position++) {
                $difference = ($b['finishes'][$position] ?? 0) <=> ($a['finishes'][$position] ?? 0);
                if ($difference !== 0) {
                    return $difference;
                }
            }

            return 0;
        };
        usort($rows, static fn (array $a, array $b): int => $compare($a, $b) ?: strcmp(mb_strtolower($a['participant']->driver->display_name), mb_strtolower($b['participant']->driver->display_name)));
        $previous = null;
        $rank = 0;
        foreach ($rows as $index => &$row) {
            if ($previous === null || $compare($previous, $row) !== 0) {
                $rank = $index + 1;
            }
            $row['rank'] = $rank;
            $previous = $row;
        }
        unset($row);

        return $rows;
    }
}
