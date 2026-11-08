<?php

namespace Tests\Unit;

use App\Services\ScoreCalculator;
use PHPUnit\Framework\TestCase;

class ScoreCalculatorTest extends TestCase
{
    private function criterion(int $id, float $weight, int $max = 5): object
    {
        return (object) ['id' => $id, 'weight' => $weight, 'max_score' => $max];
    }

    public function test_matches_kak_rubric_formula(): void
    {
        // KAK: nilai akhir = Σ(skor × bobot) ÷ 5 × 100 dengan bobot total 100%.
        $criteria = [$this->criterion(1, 20), $this->criterion(2, 15), $this->criterion(3, 15), $this->criterion(4, 15), $this->criterion(5, 15), $this->criterion(6, 10), $this->criterion(7, 10)];
        $scores = [1 => 5, 2 => 4, 3 => 3, 4 => 4, 5 => 5, 6 => 2, 7 => 3];
        $kak = (5 * 0.20 + 4 * 0.15 + 3 * 0.15 + 4 * 0.15 + 5 * 0.15 + 2 * 0.10 + 3 * 0.10) / 5 * 100;

        $this->assertEqualsWithDelta($kak, ScoreCalculator::weighted($criteria, $scores), 0.01);
    }

    public function test_normalizes_different_max_scores_and_weights(): void
    {
        $criteria = [$this->criterion(1, 1, 10), $this->criterion(2, 3, 5)];
        // (5/10*1 + 5/5*3) / 4 * 100 = 87.5
        $this->assertEquals(87.5, ScoreCalculator::weighted($criteria, [1 => 5, 2 => 5]));
    }

    public function test_out_of_range_scores_are_clamped_and_missing_count_as_zero(): void
    {
        $criteria = [$this->criterion(1, 50), $this->criterion(2, 50)];
        $this->assertEquals(50.0, ScoreCalculator::weighted($criteria, [1 => 99]));
        $this->assertNull(ScoreCalculator::weighted($criteria, []));
        $this->assertNull(ScoreCalculator::weighted([], [1 => 5]));
    }
}
