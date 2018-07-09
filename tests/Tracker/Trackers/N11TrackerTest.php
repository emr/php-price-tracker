<?php

namespace App\Tests\Tracker\Trackers;

use App\Entity\Product;
use App\Tracker\Trackers\N11Tracker;
use PHPUnit\Framework\TestCase;

class N11TrackerTest extends TestCase
{
    protected $exampleUrl = 'https://urun.n11.com/kulce-altin/nadirgold-1000-gr-kulce-altin-P99120644';

    public function testValidation() {
        $this->assertTrue(N11Tracker::validateUrl($this->exampleUrl));
    }

    public function testFetchProduct() {
        $tracker = new N11Tracker(
            new Product($this->exampleUrl)
        );

        $product = $tracker->fetchProduct();
        $prices = $product->getPrices();

        $this->assertTrue(N11Tracker::validateUrl($product->getUrl()));
        $this->assertNotEmpty($product->getTitle());
        $this->assertGreaterThanOrEqual(1, count($prices));

        foreach ($prices as $price)
        {
            $this->assertGreaterThan(190000, $price->getPrice());
            $this->assertNotEmpty($price->getCurrency());
        }
    }
}