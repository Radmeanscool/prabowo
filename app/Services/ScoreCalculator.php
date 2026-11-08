<?php

namespace App\Services;

use App\Models\ReviewCriterion;

/**
 * Perhitungan skor tertimbang di sisi server.
 *
 * Nilai akhir (0–100) = Σ(skor/maks × bobot) ÷ Σbobot × 100.
 * Untuk skala 1–5 dengan bobot total 100%, rumus ini sama dengan rubrik KAK:
 * Σ(skor × bobot) ÷ 5 × 100.
 */
class ScoreCalculator
{
    /**
     * @param  iterable<ReviewCriterion>  $criteria
     * @param  array<int, int|float|null>  $scores  criterion_id => skor
     */
    public static function weighted(iterable $criteria, array $scores): ?float
    {
        $totalWeight = 0.0;
        $sum = 0.0;
        $anyScore = false;

        foreach ($criteria as $criterion) {
            $weight = (float) $criterion->weight;
            $max = max(1, (int) $criterion->max_score);
            if ($weight <= 0) {
                continue;
            }

            $totalWeight += $weight;
            $score = $scores[$criterion->id] ?? null;
            if ($score === null || $score === '') {
                continue;
            }

            $anyScore = true;
            $bounded = min(max((float) $score, 0), $max);
            $sum += ($bounded / $max) * $weight;
        }

        if ($totalWeight <= 0 || ! $anyScore) {
            return null;
        }

        return round($sum / $totalWeight * 100, 2);
    }
}
