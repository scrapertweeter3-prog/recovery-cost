<?php

declare(strict_types=1);

namespace PoketDev\RecoveryCost;

final class RecoveryCost
{
    public function __construct(
        public readonly float $electricityRate = 0.17,
        public readonly int $sessionsPerWeek = 3,
    ) {
        if ($sessionsPerWeek < 1) {
            throw new \InvalidArgumentException('Session frequency must be at least one per week.');
        }
    }

    public function sessionsPerYear(): int
    {
        return $this->sessionsPerWeek * 52;
    }

    public function perSession(Device $device): CostBreakdown
    {
        $year = $this->sessionsPerYear();
        $amortized = $device->purchase / $device->lifeYears / $year;
        $annualEnergy = $device->annualEnergyCost($this->electricityRate, $year);
        $energy = $annualEnergy / $year;
        $care = $device->annualConsumables / $year;

        return new CostBreakdown(
            $device->name,
            $amortized,
            $energy,
            $care,
            $annualEnergy + $device->annualConsumables,
        );
    }

    public function bundlePerSession(Device ...$devices): CostBreakdown
    {
        $amortized = 0.0;
        $energy = 0.0;
        $care = 0.0;
        $annual = 0.0;
        foreach ($devices as $device) {
            $b = $this->perSession($device);
            $amortized += $b->amortizedPerSession;
            $energy += $b->energyPerSession;
            $care += $b->carePerSession;
            $annual += $b->annualOperatingCost;
        }

        return new CostBreakdown('bundle', $amortized, $energy, $care, $annual);
    }

    /**
     * Years for the gear to pay for itself against a flat membership or a
     * per-visit alternative. Null means the alternative never costs more
     * than running the gear, so no break-even arrives.
     */
    public function breakEvenYears(float $totalPurchase, float $annualAlternativeSpend, float $annualOperatingCost): ?float
    {
        $gap = $annualAlternativeSpend - $annualOperatingCost;
        if ($gap <= 0.0) {
            return null;
        }

        return $totalPurchase / $gap;
    }

    /** A flat monthly membership divided across the sessions it covers at this frequency. */
    public function membershipPerSession(float $monthlyFee): float
    {
        return $monthlyFee * 12.0 / $this->sessionsPerYear();
    }
}
