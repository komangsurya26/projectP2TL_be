<?php

namespace App\Services\Analysis\Rules;

use App\Services\Analysis\Contracts\AnalysisRule;

class SpikeRule implements AnalysisRule
{
    public function check(array $data): ?array
    {
        if (!isset($data['avg_7_days']) || !$data['avg_7_days']) return null;

        if ($data['consumption_kwh'] > ($data['avg_7_days'] * 3)) {
            return [
                'flag' => 'SPIKE',
                'score' => 30
            ];
        }

        return null;
    }
}
