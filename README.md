# recovery-cost

Recovery gear cost math for the home sauna, cold plunge, and red light panel
decision. A sticker price says nothing about what a session costs until it is
divided by service life and the sessions you actually take. This library
carries the small, repeatable arithmetic an ownership audit needs: amortized
purchase per session, session energy, annualized consumables, a flat
membership priced per session, and the break-even years where a home setup
beats the gym lounge.

```php
use PoketDev\RecoveryCost\Device;
use PoketDev\RecoveryCost\RecoveryCost;

$c = new RecoveryCost(0.17, 2); // 17 cents per kWh, two sessions a week

$sauna = Device::billedPerSession('infrared sauna', 2000.0, 10.0, 1.5, 30.0);
$plunge = Device::billedAroundTheClock('chiller plunge', 3000.0, 5.0, 30.0, 180.0);
$panel = Device::billedPerSession('red light panel', 500.0, 10.0, 0.03);

$c->perSession($sauna)->totalPerSession();   // 2.47 at two sessions a week
$c->perSession($plunge)->totalPerSession();  // 10.96, the chiller bills year round
$c->perSession($panel)->totalPerSession();   // 0.49, mostly amortization

$bundle = $c->bundlePerSession($sauna, $plunge, $panel);
$c->breakEvenYears(5500.0, 1800.0, $bundle->annualOperatingCost); // ~4.6 years vs a $150/month lounge
$c->membershipPerSession(150.0); // 17.31 per session at that frequency
```

- `perSession`: splits a session into the three parts that move it:
  amortized purchase, energy, consumables. A chiller's energy comes from a
  fixed annual figure, so its bill does not shrink when you skip sessions.
- `bundlePerSession`: prices a whole home lab the way a lounge prices a
  visit, and reports the annual operating figure the break-even needs.
- `breakEvenYears`: purchase divided by the annual spend the gear replaces
  minus the cost of running it. Null means the alternative never costs more
  than ownership, so no break-even arrives.
- `membershipPerSession`: a flat monthly fee divided across the sessions it
  covers. The weekly count moves this number more than any gear choice.

## Running the tests

    composer install
    ./vendor/bin/phpunit

MIT. Maintained by [Poket Dev](https://hackedself.com).
