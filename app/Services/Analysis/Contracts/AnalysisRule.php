<?php

namespace App\Services\Analysis\Contracts;

interface AnalysisRule
{
    public function check(array $data): ?array;
}
