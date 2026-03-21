<?php

namespace App\Services\Analysis\Rules;

use App\Services\Analysis\Contracts\AnalysisRule;

class SuddenDropRule implements AnalysisRule
{
    public function check(array $data): ?array
    {
        if (!isset($data['avg_7_days']) || !$data['avg_7_days']) return null;

        if ($data['consumption_kwh'] < ($data['avg_7_days'] * 0.3)) {
            return [
                'flag' => 'SUDDEN_DROP',
                'score' => 40
            ];
        }

        return null;
    }
}
