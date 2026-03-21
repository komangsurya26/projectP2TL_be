<?php

namespace App\Services\Analysis\Rules;

use App\Services\Analysis\Contracts\AnalysisRule;

class ZeroConsumptionRule implements AnalysisRule
{
    public function check(array $data): ?array
    {
        if ($data['consumption_kwh'] == 0) {
            return [
                'flag' => 'ZERO_CONSUMPTION',
                'score' => 50
            ];
        }

        return null;
    }
}
