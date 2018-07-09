<?php

namespace App\Tracker\Trackers;

use App\Entity\Price;
use App\Entity\Product;
use App\Tracker\AbstractTracker;

/**
 * Product tracker for n11.com
 */
class N11Tracker extends AbstractTracker
{
    /**
     * {@inheritdoc}
     */
    public static function validateUrl(string $url): bool
    {
        return preg_match('/n11.com$/', parse_url($url)['host']);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchProduct(): Product
    {
        $crawler = $this->getCrawler();

        try {
            $data = \json_decode(
                str_replace("\n", "",
                    $crawler
                        ->filterXPath('//script[@type="application/ld+json"]')
                        ->text())
            );
        } catch (\InvalidArgumentException $e) {
            throw $this->createProductNotFoundException();
        }

        $price = new Price();

        $price
            ->setPrice(floatval(str_replace(",", "", $data->mainEntity->offers->itemOffered[0]->offers->price)))
            ->setCurrency($data->mainEntity->offers->itemOffered[0]->offers->priceCurrency)
        ;

        $this->product
            ->setTitle($data->mainEntity->offers->name)
            ->setUrl($data->mainEntity->offers->url)
            ->setImageUrl($data->mainEntity->offers->itemOffered[0]->image[0]->contentUrl)
            ->addPrice($price)
        ;

        return $this->product;
    }
}