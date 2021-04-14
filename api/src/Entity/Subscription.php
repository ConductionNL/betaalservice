<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An entity representing a unique customer from a payment provider.
 *
 * This entity represents a unique customer of a payment provider.
 *
 * @author Barry Brands <barry@conduction.nl>
 * @license EUPL <https://github.com/ConductionNL/betaalservice/blob/master/LICENSE.md>
 *
 * @category entity
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get",
 *          "put",
 *          "delete",
 *          "get_change_logs"={
 *              "path"="/subscriptions/{id}/change_log",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Changelogs",
 *                  "description"="Gets al the change logs for this resource"
 *              }
 *          },
 *          "get_audit_trail"={
 *              "path"="/subscriptions/{id}/audit_trail",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Audittrail",
 *                  "description"="Gets the audit trail for this resource"
 *              }
 *          }
 *     },
 *     collectionOperations={
 *          "get",
 *          "post",
 *          "post_webhook"={
 *              "method"="POST",
 *              "path"="subscriptions/mollie_webhook",
 *              "input_formats"={"x-www-form-urlencoded"={"application/x-www-form-urlencoded"}},
 *              "swagger_context" = {
 *                  "summary"="Webhook to update subscription statuses from Mollie",
 *                  "description"="Webhook to update subscription statuses from Mollie"
 *              }
 *          }
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\SubscriptionRepository")
 * @Gedmo\Loggable(logEntryClass="Conduction\CommonGroundBundle\Entity\ChangeLog")
 *
 * @ApiFilter(OrderFilter::class)
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(SearchFilter::class)
 */
class Subscription
{
    /**
     * @var UuidInterface
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\Uuid
     * @Groups({"read"})
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private $id;

    /**
     * @var string The subscription id from the payment provider
     *
     * @example randomid_1234778
     *
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $subscriptionId;

    /***
     * @var array The subscription from the payment service
     *
     * @example ['id' => 'abc_123153', 'name' => 'Subscription for John Doe']
     *
     * @Gedmo\Versioned
     * @Groups({"read"})
     * @ORM\Column(type="array", nullable=true)
     */
    private $subscriptionFromService = [];

    /**
     * @var string The organization this subscription is offered by
     *
     * @Groups({"read", "write"})
     * @Assert\Url
     * @Assert\Length(
     *     max=255
     * )
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $organization;

    /**
     * @var Customer The customer this subscriptions belongs to
     *
     * @Groups({"read","write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer", inversedBy="subscriptions")
     * @ORM\JoinColumn(nullable=false)
     * @MaxDepth(1)
     */
    private $customer;

    /**
     * @var Service The service this subscription uses
     *
     * @Groups({"read","write"})
     * @ORM\ManyToOne(targetEntity=Service::class, inversedBy="subscriptions")
     * @ORM\JoinColumn(nullable=false)
     * @MaxDepth(1)
     */
    private $service;

    /**
     * @var Datetime The moment this request was created
     *
     * @Groups({"read"})
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateCreated;

    /**
     * @var Datetime The moment this request last Modified
     *
     * @Groups({"read"})
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */

    private $dateModified;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->invoices = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSubscriptionId(): ?string
    {
        return $this->subscriptionId;
    }

    public function setSubscriptionId(string $subscriptionId): self
    {
        $this->subscriptionId = $subscriptionId;

        return $this;
    }

    public function getSubscriptionFromService(): ?array
    {
        return $this->subscriptionFromService;
    }

    public function setSubscriptionFromService(?array $subscriptionFromService): self
    {
        $this->subscriptionFromService = $subscriptionFromService;

        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(string $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateModified(): ?\DateTimeInterface
    {
        return $this->dateModified;
    }

    public function setDateModified(\DateTimeInterface $dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }
}
