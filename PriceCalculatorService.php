<?php

namespace App\Components\Services;

use App\Models\Quote;
use App\Components\Services\Calculators\BaseTaxPriceCalculator;
use App\Components\Services\Calculators\SpindlePriceCalculator;
use App\Components\Services\Calculators\BaseRailPriceCalculator;
use App\Components\Services\Calculators\HandrailPriceCalculator;
use App\Components\Services\Calculators\StringerPriceCalculator;
use App\Components\Services\Calculators\NewelCapsPriceCalculator;
use App\Components\Services\Calculators\FixingKitsPriceCalculator;
use App\Components\Services\Calculators\NewelPostsPriceCalculator;
use App\Components\Services\Calculators\BaseAddonsPriceCalculator;
use App\Components\Services\Calculators\StraitBasePriceCalculator;
use App\Components\Services\Calculators\WindersBasePriceCalculator;
use App\Components\Services\Calculators\ExtraPackingPriceCalculator;
use App\Components\Services\Calculators\BaseDeliveryPriceCalculator;
use App\Components\Services\Calculators\TreadsProfilePriceCalculator;
use App\Components\Services\Calculators\FeatureTreadsPriceCalculator;
use App\Components\Services\Calculators\Core\PriceCalculatorInterface;
use App\Components\Services\Calculators\TwoMenDeliveryPriceCalculator;
use App\Components\Services\Calculators\TreadsMaterialPriceCalculator;
use App\Components\Services\Calculators\RisersMaterialPriceCalculator;
use App\Components\Services\Calculators\CutToSizeZipboltPriceCalculator;
use App\Components\Services\Calculators\BaseFloorToFloorPriceCalculator;
use App\Components\Services\Calculators\AdditionalSpindlePriceCalculator;
use App\Components\Services\Calculators\AdditionalBaserailPriceCalculator;
use App\Components\Services\Calculators\AdditionalHandrailPriceCalculator;
use App\Components\Services\Calculators\AdditionalNewelPostsPriceCalculator;

class PriceCalculatorService
{
    protected array $subtotalComponents = [
        RisersMaterialPriceCalculator::class,
        TreadsProfilePriceCalculator::class,
        TreadsMaterialPriceCalculator::class,
        StraitBasePriceCalculator::class,
        WindersBasePriceCalculator::class,
        FeatureTreadsPriceCalculator::class,
        NewelPostsPriceCalculator::class,
        AdditionalNewelPostsPriceCalculator::class,
        NewelCapsPriceCalculator::class,
        HandrailPriceCalculator::class,
        AdditionalHandrailPriceCalculator::class,
        BaseRailPriceCalculator::class,
        AdditionalBaserailPriceCalculator::class,
        SpindlePriceCalculator::class,
        AdditionalSpindlePriceCalculator::class,
        StringerPriceCalculator::class,
    ];
    protected array $addonComponents = [
        TwoMenDeliveryPriceCalculator::class,
        FixingKitsPriceCalculator::class,
        ExtraPackingPriceCalculator::class,
        CutToSizeZipboltPriceCalculator::class,
    ];
    protected array $taxesComponents = [];
    protected array $deliveryComponents = [];

    /**
     * @param Quote $quote
     * @return float
     */
    public function getSubtotal(Quote $quote): float
    {
        return $this->getSubtotalDecorator($quote)->getPrice();
    }

    /**
     * @param Quote $quote
     * @return float
     */
    public function getAddonSubtotal(Quote $quote) : float
    {
        return $this->getAddonDecorator($quote)->getPrice();
    }

    /**
     * @param Quote $quote
     * @return float
     */
    public function getTax(Quote $quote): float
    {
        return $this->getTaxDecorator($quote)->getPrice();
    }

    /**
     * @param Quote $quote
     * @return float
     */
    public function getDelivery(Quote $quote): float
    {
        return $this->getDeliveryDecorator($quote)->getPrice();
    }

    /**
     * @param Quote $quote
     * @return array
     */
    public function getPriceBreakdown(Quote $quote): array
    {
        $subtotal = $this->getSubtotalDecorator($quote)->getPriceBreakdown();
        $delivery = $this->getDeliveryDecorator($quote)->getPriceBreakdown();
        $addonSubtotal = $this->getAddonDecorator($quote)->getPriceBreakdown();

        return [...$subtotal, ...$addonSubtotal, ...$delivery];
    }

    public function getAddonBreakdown(Quote $quote): array
    {
        return $this->getAddonDecorator($quote)->getPriceBreakdown();
    }

    /**
     * @param Quote $quote
     * @return PriceCalculatorInterface
     */
    protected function getSubtotalDecorator(Quote $quote): PriceCalculatorInterface
    {
        $options = json_decode($quote->staircase, true);
        /** @var PriceCalculatorInterface $lastComponent */
        $lastComponent = new BaseFloorToFloorPriceCalculator($options);
        foreach ($this->subtotalComponents as $component) {
            $lastComponent = new $component($lastComponent, $options);
        }

        return $lastComponent;
    }

    /**
     * @param Quote $quote
     * @return PriceCalculatorInterface
     */
    protected function getTaxDecorator(Quote $quote): PriceCalculatorInterface
    {
        $options = config('mrstairs.taxes');
        $options['subtotal'] = $this->getSubtotal($quote) + $this->getAddonSubtotal($quote);
        $options['delivery'] = $this->getDelivery($quote);
        /** @var PriceCalculatorInterface $lastComponent */
        $lastComponent = new BaseTaxPriceCalculator($options);

        foreach ($this->taxesComponents as $component) {
            $lastComponent = new $component($lastComponent, $options);
        }

        return $lastComponent;
    }

    /**
     * @param Quote $quote
     * @return PriceCalculatorInterface
     */
    protected function getDeliveryDecorator(Quote $quote): PriceCalculatorInterface
    {
        $options = json_decode($quote->staircase, true);
        /** @var PriceCalculatorInterface $lastComponent */
        $lastComponent = new BaseDeliveryPriceCalculator($options);

        foreach ($this->deliveryComponents as $component) {
            $lastComponent = new $component($lastComponent, $options);
        }

        return $lastComponent;
    }

    protected function getAddonDecorator(Quote $quote): PriceCalculatorInterface
    {
        $options = json_decode($quote->staircase, true);
        /** @var PriceCalculatorInterface $lastComponent */
        $lastComponent = new BaseAddonsPriceCalculator($options);

        foreach ($this->addonComponents as $component) {
            $lastComponent = new $component($lastComponent, $options);
        }

        return $lastComponent;
    }
}
