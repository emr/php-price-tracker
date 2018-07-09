<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Service\TrackingManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class TrackingManagerSubscriber implements EventSubscriber
{
    /** @var TrackingManager */
    protected $trackingManager;

    public function __construct(TrackingManager $trackingManager)
    {
        $this->trackingManager = $trackingManager;
    }

    public function getSubscribedEvents()
    {
        return [
            'postUpdate',
            'postPersist',
        ];
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    protected function index(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        if (!$object instanceof Product)
            return;

        $this->trackingManager->stopTracking();
        $this->trackingManager->startTracking();
    }
}