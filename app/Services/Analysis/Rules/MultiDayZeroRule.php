<?php

namespace App\Services\Analysis\Rules;

use App\Services\Analysis\Contracts\AnalysisRule;

class MultiDayZeroRule implements AnalysisRule
{
    public function check(array $data): ?array
    {
        if (!isset($data['zero_days_count'])) return null;

        if ($data['zero_days_count'] >= 3) {
            return [
                'flag' => 'ZERO_3_DAYS',
                'score' => 80
            ];
        }

        return null;
    }
}