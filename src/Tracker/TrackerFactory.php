<?php

namespace App\Tracker;

use App\Entity\Product;
use App\Tracker\Trackers;
use App\Exception\InvalidUrlException;
use App\Exception\UnsupportedSiteException;

class TrackerFactory
{
    private static $trackers = [Trackers\N11Tracker::class];

    /**
     * @throws UnsupportedSiteException
     * @throws InvalidUrlException
     * @param string $url
     * @return TrackerInterface
     */
    public static function createFromURL(string $url): TrackerInterface
    {
        if (!filter_var($url, FILTER_VALIDATE_URL))
            throw new InvalidUrlException("Url '{$url}' is invalid.");

        foreach (self::$trackers as $class)
        {
            if ($class::validateUrl($url))
            {
                return new $class(new Product($url));
            }
        }

        throw new UnsupportedSiteException(sprintf("Site '%s' is not supported.", parse_url($url)['host']));
    }

    /**
     * @throws UnsupportedSiteException
     * @param Product $product
     * @return TrackerInterface
     */
    public static function createFromProduct(Product $product): TrackerInterface
    {
        $url = $product->getUrl();

        if (!filter_var($url, FILTER_VALIDATE_URL))
            throw new InvalidUrlException("Url '{$url}' is invalid.");

        foreach (self::$trackers as $class)
        {
            if ($class::validateUrl($url))
            {
                return new $class($product);
            }
        }

        throw new UnsupportedSiteException(sprintf("The site '%s' is not supported.", parse_url($url)['host']));
    }
}