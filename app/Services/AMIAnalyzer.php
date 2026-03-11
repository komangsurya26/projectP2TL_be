<?php

namespace App\Services;

use App\Models\AnalisaResult;
use App\Models\AnomalyLog;

class AMIAnalyzer
{
    private int $riskScore = 0;
    private array $logs = [];

    public function analyze(array $data)
    {
        $voltages = $this->clean([
            $data['voltage_r'] ?? null,
            $data['voltage_s'] ?? null,
            $data['voltage_t'] ?? null
        ]);

        $currents = $this->clean([
            $data['current_r'] ?? null,
            $data['current_s'] ?? null,
            $data['current_t'] ?? null
        ]);

        // Analisis
        $this->voltageAbnormal($voltages);
        $this->voltageUnbalance($voltages);

        $this->currentUnbalance($currents);
        $this->neutralBypass($currents, $data['current_netral'] ?? null);

        $this->powerFactor($data['pf'] ?? null);
        $this->phaseLoss($voltages);
        $this->zeroConsumption($voltages, $currents);

        // Simpan hasil
        return $this->save($data);
    }

    /*
    ======================
    Helpers
    ======================
    */

    private function clean(array $values)
    {
        return array_filter($values, fn($v) => $v !== null);
    }

    private function risk($score, $type, $value, $threshold)
    {
        $this->riskScore += $score;

        $this->logs[] = [
            'jenis_anomali' => $type,
            'nilai' => $value,
            'threshold' => $threshold
        ];
    }

    /*
    ======================
    Voltage Analysis
    ======================
    */

    private function voltageAbnormal($voltages)
    {
        foreach ($voltages as $v) {
            if ($v < 190 || $v > 260) {
                $this->risk(20, 'VOLTAGE_ABNORMAL', $v, '190-260');
            }
        }
    }

    private function voltageUnbalance($voltages)
    {
        if (count($voltages) < 2) return;

        $avg = array_sum($voltages) / count($voltages);

        foreach ($voltages as $v) {
            $u = abs($v - $avg) / $avg;
            if ($u > 0.1) {
                $this->risk(20, 'VOLTAGE_UNBALANCE', $u, 0.1);
                break;
            }
        }
    }

    /*
    ======================
    Current Analysis
    ======================
    */

    private function currentUnbalance($currents)
    {
        if (count($currents) < 2) return;

        $avg = array_sum($currents) / count($currents);

        foreach ($currents as $c) {
            $u = abs($c - $avg) / $avg;
            if ($u > 0.3) {
                $this->risk(25, 'CURRENT_UNBALANCE', $u, 0.3);
                break;
            }
        }
    }

    /*
    ======================
    Neutral Current
    ======================
    */

    private function neutralBypass($currents, $neutral)
    {
        if ($neutral === null || count($currents) == 0) return;

        $avg = array_sum($currents) / count($currents);

        if ($neutral > ($avg * 0.5)) {
            $this->risk(35, 'HIGH_NEUTRAL_CURRENT', $neutral, $avg * 0.5);
        }
    }

    /*
    ======================
    Power Factor
    ======================
    */

    private function powerFactor($pf)
    {
        if ($pf === null) return;

        if ($pf < 0.7) {
            $this->risk(20, 'LOW_POWER_FACTOR', $pf, 0.7);
        }
    }

    /*
    ======================
    Phase Loss
    ======================
    */

    private function phaseLoss($voltages)
    {
        foreach ($voltages as $v) {
            if ($v < 50) {
                $this->risk(30, 'PHASE_LOSS', $v, '>50');
            }
        }
    }

    /*
    ======================
    Zero Consumption
    ======================
    */

    private function zeroConsumption($voltages, $currents)
    {
        if (count($voltages) == 0 || count($currents) == 0) return;

        $avgV = array_sum($voltages) / count($voltages);
        $avgI = array_sum($currents) / count($currents);

        if ($avgV > 200 && $avgI == 0) {
            $this->risk(40, 'ZERO_CURRENT_WITH_VOLTAGE', $avgI, '>0');
        }
    }

    /*
    ======================
    Save Result
    ======================
    */

    private function save(array $data)
    {
        $status = 'NORMAL';

        if ($this->riskScore >= 60) {
            $status = 'ANOMALY';
        } elseif ($this->riskScore >= 30) {
            $status = 'SUSPECT';
        }

        $result = AnalisaResult::create([
            'idpel' => $data['idpel'],
            'risk_score' => $this->riskScore,
            'status' => $status,
            'analisa_at' => now()
        ]);

        $logRows = [];
        foreach ($this->logs as $log) {
            $logRows[] = [
                'idpel' => $data['idpel'],
                'jenis_anomali' => $log['jenis_anomali'],
                'nilai' => $log['nilai'],
                'threshold' => $log['threshold'],
                'sumber_data' => 'AMI'
            ];
        }

        if (!empty($logRows)) {
            AnomalyLog::insert($logRows); // batch insert
        }

        return $result;
    }
}
