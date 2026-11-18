<?php

declare(strict_types=1);

namespace PoketDev\RecoveryCost;

final class Device
{
    public const ENERGY_PER_SESSION = 'per_session';
    public const ENERGY_ANNUAL = 'annual';

    public function __construct(
        public readonly string $name,
        public readonly float $purchase,
        public readonly float $lifeYears,
        public readonly float $energyAmount,
        public readonly float $annualConsumables,
        public readonly string $energyMode = self::ENERGY_PER_SESSION,
    ) {
        if ($lifeYears <= 0) {
            throw new \InvalidArgumentException('Service life must be positive.');
        }
    }

    /** A device billed in kilowatt hours per session: an infrared cabin, a red light panel. */
    public static function billedPerSession(
        string $name,
        float $purchase,
        float $lifeYears,
        float $kwhPerSession,
        float $annualConsumables = 0.0,
    ): self {
        return new self($name, $purchase, $lifeYears, $kwhPerSession, $annualConsumables, self::ENERGY_PER_SESSION);
    }

    /** A chiller-style device that holds water temperature around the clock and bills a fixed monthly energy figure. */
    public static function billedAroundTheClock(
        string $name,
        float $purchase,
        float $lifeYears,
        float $monthlyEnergyDollars,
        float $annualConsumables = 0.0,
    ): self {
        return new self($name, $purchase, $lifeYears, $monthlyEnergyDollars, $annualConsumables, self::ENERGY_ANNUAL);
    }

    public function annualEnergyCost(float $electricityRate, int $sessionsPerYear): float
    {
        return $this->energyMode === self::ENERGY_PER_SESSION
            ? $this->energyAmount * $electricityRate * $sessionsPerYear
            : $this->energyAmount * 12.0;
    }
}
