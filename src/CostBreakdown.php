<?php

declare(strict_types=1);

namespace PoketDev\RecoveryCost;

final class CostBreakdown
{
    public function __construct(
        public readonly string $device,
        public readonly float $amortizedPerSession,
        public readonly float $energyPerSession,
        public readonly float $carePerSession,
        public readonly float $annualOperatingCost,
    ) {}

    public function totalPerSession(): float
    {
        return $this->amortizedPerSession + $this->energyPerSession + $this->carePerSession;
    }

    /** @return array{device:string, amortized:float, energy:float, care:float, total:float, annual_operating:float} */
    public function toArray(): array
    {
        return [
            'device' => $this->device,
            'amortized' => $this->amortizedPerSession,
            'energy' => $this->energyPerSession,
            'care' => $this->carePerSession,
            'total' => $this->totalPerSession(),
            'annual_operating' => $this->annualOperatingCost,
        ];
    }
}
