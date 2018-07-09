<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="products")
 * @ORM\HasLifecycleCallbacks()
 * @ApiResource(
 *     collectionOperations={"get", "track"={"route_name"="track"}},
 *     itemOperations={"get", "delete"}
 * )
 */
class Product
{
    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     * @ORM\Id()
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $imageUrl;

    /**
     * @var Collection|Price[]
     * @ORM\OneToMany(targetEntity="App\Entity\Price", mappedBy="product", cascade={"persist"})
     * @ORM\OrderBy({"id": "DESC"})
     */
    private $prices = [];

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $intervalTime;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="products")
     */
    private $user;

    /**
     * @var Collection|NotificationRule[]
     * @ORM\OneToMany(targetEntity="App\Entity\NotificationRule", mappedBy="product")
     */
    private $notificationRules;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $nextTrackingTime;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    public function __construct(string $url = null)
    {
        $this->prices = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->setUrl($url);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $title
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $url
     * @return self
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $imageUrl
     * @return self
     */
    public function setImageUrl(string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * @param Price $price
     * @return self
     */
    public function addPrice(Price $price): self
    {
        $this->prices->add($price->setProduct($this));

        $this->updateNextTrackingTime();

        return $this;
    }

    /**
     * @return Collection|Price[]
     */
    public function getPrices(): Collection
    {
        return $this->prices;
    }

    /**
     * @param int $intervalTime
     * @return self
     */
    public function setIntervalTime(int $intervalTime): self
    {
        $this->intervalTime = $intervalTime;

        $this->updateNextTrackingTime();

        return $this;
    }

    /**
     * @return int
     */
    public function getIntervalTime(): int
    {
        return $this->intervalTime;
    }

    /**
     * @param null|User $user
     * @return self
     */
    public function setUser(?User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return null|User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param NotificationRule $rule
     */
    public function addNotificationRule(NotificationRule $rule)
    {
        $this->notificationRules->add($rule);
    }

    /**
     * @return null|Collection|NotificationRule[]
     */
    public function getNotificationRules(): ?Collection
    {
        return $this->notificationRules;
    }

    /**
     * @param \DateTime $nextTrackingTime
     * @return self
     */
    public function setNextTrackingTime(\DateTime $nextTrackingTime): self
    {
        $this->nextTrackingTime = $nextTrackingTime;

        return $this;
    }

    /**
     * @return self
     */
    public function updateNextTrackingTime(): self
    {
        if ($this->intervalTime)
            $this->setNextTrackingTime(
                (new \DateTime())->add(new \DateInterval("PT{$this->intervalTime}M"))
            );

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getNextTrackingTime(): \DateTime
    {
        return $this->nextTrackingTime;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return self
     */
    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PreUpdate()
     * @return self
     */
    public function update(): self
    {
        $this->setUpdatedAt(new \DateTime());

        return $this;
    }

    public function __toString()
    {
        return "Product (id: {$this->id})";
    }
}