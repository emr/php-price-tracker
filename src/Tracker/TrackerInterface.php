<?php

namespace App\Tracker;

use App\Entity\Product;
use App\Exception\ProductNotFoundException;

interface TrackerInterface
{
    /**
     * @param string $url
     * @return bool
     */
    public static function validateUrl(string $url): bool;

    /**
     * Fetch actual product detail
     * @throws ProductNotFoundException
     * @return Product
     */
    public function fetchProduct(): Product;

    /**
     * Get current product detail
     * @return Product
     */
    public function getProduct(): Product;
}