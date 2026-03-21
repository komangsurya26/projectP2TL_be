<?php

namespace App\Services\Analysis\Rules;

use App\Services\Analysis\Contracts\AnalysisRule;

class HighConsumptionRule implements AnalysisRule
{
    public function check(array $data): ?array
    {
        if ($data['consumption_kwh'] > 200) {
            return [
                'flag' => 'HIGH_CONSUMPTION',
                'score' => 20
            ];
        }

        return null;
    }
}
