<?php

namespace App\Tracker;

use App\Entity\Product;
use App\Exception\ProductNotFoundException;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractTracker implements TrackerInterface
{
    /** @var Client */
    protected $client;

    /** @var Crawler */
    protected $crawler;

    /** @var Product */
    protected $product;

    /** @var array */
    protected $observers;

    public function __construct(Product $product)
    {
        $this->client = new Client();
        $this->product = $product;
    }

    /**
     * @return Crawler
     */
    public function getCrawler(): Crawler
    {
        return $this->crawler = $this->crawler ?: $this->client->request('GET', $this->product->getUrl());
    }

    /**
     * @return ProductNotFoundException
     */
    protected function createProductNotFoundException()
    {
        return new ProductNotFoundException("Product not found in '{$this->product->getUrl()}'");
    }

    /**
     * {@inheritdoc}
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * {@inheritdoc}
     */
    public abstract function fetchProduct(): Product;
}