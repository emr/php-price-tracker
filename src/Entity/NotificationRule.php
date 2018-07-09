<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Fresh\DoctrineEnumBundle\Validator\Constraints as DoctrineAssert;
use App\DBAL\Types\NotificationRuleDirectionType;
use App\DBAL\Types\NotificationRuleUnitType;

/**
 * @ORM\Entity()
 * @ORM\Table("product_notification_rules")
 */
class NotificationRule
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     * @ORM\Id()
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $value;

    /**
     * @var string|NotificationRuleUnitType
     * @ORM\Column(name="unit", type="NotificationRuleUnitType", nullable=false)
     * @DoctrineAssert\Enum(entity="App\DBAL\Types\NotificationRuleUnitType")
     */
    private $unit;

    /**
     * @var string|NotificationRuleDirectionType
     * @ORM\Column(name="direction", type="NotificationRuleDirectionType", nullable=false)
     * @DoctrineAssert\Enum(entity="App\DBAL\Types\NotificationRuleDirectionType")
     */
    private $direction;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $notified;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="notificationRules")
     */
    private $product;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $value
     * @return NotificationRule
     */
    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @param string $unit
     * @return NotificationRule
     */
    public function setUnit(string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * @param string $direction
     */
    public function setDirection(string $direction)
    {
        $this->direction = $direction;
    }

    /**
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @param bool $notified
     */
    public function setNotified(bool $notified)
    {
        $this->notified = $notified;
    }

    /**
     * @return bool
     */
    public function isNotified(): bool
    {
        return $this->notified;
    }

    /**
     * @param Product $product
     * @return NotificationRule
     */
    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }
}