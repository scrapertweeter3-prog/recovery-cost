<?php

declare(strict_types=1);

namespace PoketDev\RecoveryCost;

if (!function_exists('recovery_cost_per_session')) {
    /**
     * @param array{name:string, purchase:float, life_years:float, energy_amount:float, annual_consumables?:float, energy_mode?:string} $device
     * @return array{device:string, amortized:float, energy:float, care:float, total:float, annual_operating:float}
     */
    function recovery_cost_per_session(array $device, float $electricityRate = 0.17, int $sessionsPerWeek = 3): array
    {
        $mode = $device['energy_mode'] ?? Device::ENERGY_PER_SESSION;
        $d = $mode === Device::ENERGY_ANNUAL
            ? Device::billedAroundTheClock(
                $device['name'],
                $device['purchase'],
                $device['life_years'],
                $device['energy_amount'],
                $device['annual_consumables'] ?? 0.0
            )
            : Device::billedPerSession(
                $device['name'],
                $device['purchase'],
                $device['life_years'],
                $device['energy_amount'],
                $device['annual_consumables'] ?? 0.0
            );

        return (new RecoveryCost($electricityRate, $sessionsPerWeek))->perSession($d)->toArray();
    }
}

if (!function_exists('recovery_break_even_years')) {
    function recovery_break_even_years(
        float $totalPurchase,
        float $annualAlternativeSpend,
        float $annualOperatingCost
    ): ?float {
        return (new RecoveryCost())->breakEvenYears($totalPurchase, $annualAlternativeSpend, $annualOperatingCost);
    }
}
