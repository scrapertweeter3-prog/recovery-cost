<?php

declare(strict_types=1);

namespace PoketDev\RecoveryCost\Tests;

use PHPUnit\Framework\TestCase;
use PoketDev\RecoveryCost\Device;
use PoketDev\RecoveryCost\RecoveryCost;

final class RecoveryCostTest extends TestCase
{
    private function sauna(): Device
    {
        return Device::billedPerSession('infrared sauna', 2000.0, 10.0, 1.5, 30.0);
    }

    private function plunge(): Device
    {
        return Device::billedAroundTheClock('chiller plunge', 3000.0, 5.0, 30.0, 180.0);
    }

    private function panel(): Device
    {
        return Device::billedPerSession('red light panel', 500.0, 10.0, 0.03);
    }

    private function calc(int $sessionsPerWeek): RecoveryCost
    {
        return new RecoveryCost(0.17, $sessionsPerWeek);
    }

    public function testSaunaPerSessionMatchesWorkedExample(): void
    {
        $this->assertSame(2.47, round($this->calc(2)->perSession($this->sauna())->totalPerSession(), 2));
        $this->assertSame(1.73, round($this->calc(3)->perSession($this->sauna())->totalPerSession(), 2));
        $this->assertSame(1.14, round($this->calc(5)->perSession($this->sauna())->totalPerSession(), 2));
    }

    public function testSaunaAmortizationDwarfsItsElectricity(): void
    {
        $b = $this->calc(3)->perSession($this->sauna());
        $this->assertSame(1.28, round($b->amortizedPerSession, 2));
        $this->assertSame(0.26, round($b->energyPerSession, 2));
        $this->assertSame(0.19, round($b->carePerSession, 2));
        $this->assertTrue($b->amortizedPerSession > $b->energyPerSession * 5);
    }

    public function testPlungePerSessionMatchesWorkedExample(): void
    {
        $this->assertSame(10.96, round($this->calc(2)->perSession($this->plunge())->totalPerSession(), 2));
        $this->assertSame(7.31, round($this->calc(3)->perSession($this->plunge())->totalPerSession(), 2));
        $this->assertSame(4.38, round($this->calc(5)->perSession($this->plunge())->totalPerSession(), 2));
    }

    public function testPlungeAnnualBillIsFrequencyIndependent(): void
    {
        $annual2 = $this->calc(2)->perSession($this->plunge())->annualOperatingCost;
        $annual5 = $this->calc(5)->perSession($this->plunge())->annualOperatingCost;
        $this->assertSame(540.0, round($annual2, 2));
        $this->assertSame(round($annual2, 2), round($annual5, 2));
    }

    public function testPanelPerSessionMatchesWorkedExample(): void
    {
        $this->assertSame(0.49, round($this->calc(2)->perSession($this->panel())->totalPerSession(), 2));
        $this->assertSame(0.33, round($this->calc(3)->perSession($this->panel())->totalPerSession(), 2));
        $this->assertSame(0.20, round($this->calc(5)->perSession($this->panel())->totalPerSession(), 2));
    }

    public function testBundleTableSumsTheRoundedRows(): void
    {
        $per = fn (Device $d, int $wk) => round($this->calc($wk)->perSession($d)->totalPerSession(), 2);
        $this->assertEqualsWithDelta(13.92, $per($this->sauna(), 2) + $per($this->plunge(), 2) + $per($this->panel(), 2), 0.001);
        $this->assertEqualsWithDelta(9.37, $per($this->sauna(), 3) + $per($this->plunge(), 3) + $per($this->panel(), 3), 0.001);
        $this->assertEqualsWithDelta(5.72, $per($this->sauna(), 5) + $per($this->plunge(), 5) + $per($this->panel(), 5), 0.001);
    }

    public function testBreakEvenYearsMatchTheArticleThresholds(): void
    {
        $c = $this->calc(2);
        $bundle = $c->bundlePerSession($this->sauna(), $this->plunge(), $this->panel());
        $operating = round($bundle->annualOperatingCost, 2);
        $this->assertSame(597.05, $operating);
        $this->assertSame(4.6, round($c->breakEvenYears(5500.0, 1800.0, $operating), 1));
        $this->assertSame(3.1, round($c->breakEvenYears(5500.0, 2400.0, $operating), 1));
        $this->assertSame(1.8, round($c->breakEvenYears(5500.0, 3640.0, $operating), 1));
    }

    public function testNoBreakEvenWhenTheAlternativeStaysCheaper(): void
    {
        $this->assertNull($this->calc(3)->breakEvenYears(5500.0, 500.0, 597.05));
    }

    public function testMembershipPerSessionFollowsFlatFeeDivision(): void
    {
        $this->assertEqualsWithDelta(11.5385, $this->calc(3)->membershipPerSession(150.0), 0.001);
        $this->assertEqualsWithDelta(17.3077, $this->calc(2)->membershipPerSession(150.0), 0.001);
    }

    public function testHelpersWrapTheClass(): void
    {
        $row = \PoketDev\RecoveryCost\recovery_cost_per_session(
            ['name' => 'infrared sauna', 'purchase' => 2000.0, 'life_years' => 10.0, 'energy_amount' => 1.5, 'annual_consumables' => 30.0],
            0.17,
            2
        );
        $this->assertSame(2.47, round($row['total'], 2));
        $this->assertSame(4.6, round((float) \PoketDev\RecoveryCost\recovery_break_even_years(5500.0, 1800.0, 597.05), 1));
    }

    public function testZeroLifeRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Device('broken', 1000.0, 0.0, 1.0, 0.0);
    }
}
