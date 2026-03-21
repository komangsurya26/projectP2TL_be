<?php

namespace App\Services\Analysis\Rules;

use App\Services\Analysis\Contracts\AnalysisRule;

class LowConsumptionRule implements AnalysisRule
{
    public function check(array $data): ?array
    {
        if ($data['consumption_kwh'] < 1) {
            return [
                'flag' => 'LOW_CONSUMPTION',
                'score' => 30
            ];
        }

        return null;
    }
}
