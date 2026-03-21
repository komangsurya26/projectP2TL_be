<?php

namespace App\Services\Analysis;

use App\Services\Analysis\Rules\LowConsumptionRule;
use App\Services\Analysis\Rules\HighConsumptionRule;
use App\Services\Analysis\Rules\ZeroConsumptionRule;
use App\Services\Analysis\Rules\SuddenDropRule;
use App\Services\Analysis\Rules\SpikeRule;
use App\Services\Analysis\Rules\MultiDayZeroRule;

class Analyzer
{
    protected $rules = [];

    public function __construct()
    {
        $this->rules = [
            new LowConsumptionRule(),
            new HighConsumptionRule(),
            new ZeroConsumptionRule(),
            new SuddenDropRule(),
            new SpikeRule(),
            new MultiDayZeroRule(),
        ];
    }

    public function analyze(array $data): array
    {
        $totalScore = 0;
        $flags = [];

        foreach ($this->rules as $rule) {
            $result = $rule->check($data);

            if ($result) {
                $flags[] = $result['flag'];
                $totalScore += $result['score'];
            }
        }

        return [
            'flags' => $flags,
            'score' => $totalScore,
            'status' => $this->resolveStatus($totalScore)
        ];
    }

    private function resolveStatus($score)
    {
        if ($score >= 70) return 'ANOMALY';
        if ($score >= 40) return 'SUSPECT';
        return 'NORMAL';
    }
}
