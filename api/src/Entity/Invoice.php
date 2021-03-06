<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An entity representing an invoice.
 *
 * This entity represents an invoice for sales
 *
 * @author Barry Brands <barry@conduction.nl>
 *
 * @category entity
 *
 * @license EUPL <https://github.com/ConductionNL/betaalservice/blob/master/LICENSE.md>
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get",
 *          "put",
 *          "delete",
 *          "get_change_logs"={
 *              "path"="/invoices/{id}/change_log",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Changelogs",
 *                  "description"="Gets al the change logs for this resource"
 *              }
 *          },
 *          "get_audit_trail"={
 *              "path"="/invoices/{id}/audit_trail",
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
 *          "post_order"={
 *              "method"="POST",
 *              "path"="order",
 *              "swagger_context" = {
 *                  "summary"="Create an invoice by just providing an order",
 *                  "description"="Create an invoice by just providing an order"
 *              }
 *          },
 *          "post_create_subscription"={
 *              "method"="POST",
 *              "path"="create_subscription",
 *              "swagger_context" = {
 *                  "summary"="Create an subscription by just providing an invoice",
 *                  "description"="Create an subscription by just providing an invoice"
 *              }
 *          },
 *          "post_status"={
 *              "method"="POST",
 *              "path"="status",
 *              "swagger_context" = {
 *                  "summary"="Check status of mollie payment",
 *                  "description"="Check status of mollie payment"
 *              }
 *          }
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\InvoiceRepository")
 * @Gedmo\Loggable(logEntryClass="Conduction\CommonGroundBundle\Entity\ChangeLog")
 * @ORM\Table(name="invoices")
 * @ORM\HasLifecycleCallbacks
 *
 * @ApiFilter(OrderFilter::class)
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(SearchFilter::class, properties={
 *     "id":"exact",
 *     "name":"partial",
 *     "order":"exact",
 *     "customer":"exact",
 *     "status":"exact",
 *     "organization":"exact"
 * })
 */
class Invoice
{
    /**
     * @var UuidInterface The UUID identifier of this object
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     *
     * @Groups({"read"})
     * @Assert\Uuid
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private $id;

    /**
     * @var string The name of the invoice
     *
     * @Gedmo\Versioned
     *
     * @example My Invoice
     * @Groups({"read","write"})
     * @Assert\Length(
     *     max=255
     * )
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string The description of the invoice
     *
     * @Gedmo\Versioned
     *
     * @example This is the best invoice ever
     * @Groups({"read","write"})
     * @Assert\Length(
     *     max=255
     * )
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $description;

    /**
     * @var string The human readable reference for this request, build as {gemeentecode}-{year}-{referenceId}. Where gemeentecode is a four digit number for gemeenten and a four letter abriviation for other organizations
     *
     * @example 6666-2019-0000000012
     *
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ApiFilter(SearchFilter::class, strategy="exact")
     * @Assert\Length(
     *     max = 255
     * )
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private $reference;

    /**
     * @var string The autoincrementing id part of the reference, unique on a organization-year-id basis
     *
     * @Gedmo\Versioned
     * @Assert\Length(
     *     max = 11
     * )
     * @ORM\Column(type="integer", length=11, nullable=true)
     */
    private $referenceId;

    /**
     * @var string The organization this invoice belongs to
     *
     * @Groups({"read", "write"})
     * @Assert\Url
     * @Assert\Length(
     *     max=255
     * )
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $organization;

//    /**
//     * @var string The RSIN of the organization that owns this process
//     *
//     * @example 002851234
//     *
//     * @Gedmo\Versioned
//     * @Assert\Length(
//     *     max = 255
//     * )
//     * @Groups({"read", "write"})
//     * @ORM\Column(type="string", length=255, nullable=true)
//     * @ApiFilter(SearchFilter::class, strategy="exact")
//     */
//    private $targetOrganization;

    /**
     * @var ArrayCollection The items in this invoice
     *
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\InvoiceItem", mappedBy="invoice", cascade={"persist"})
     * @MaxDepth(1)
     */
    private $items;

    /**
     * @var float The price of this product
     *
     * @example 50.00
     *
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="decimal", nullable=true)
     */
    private $price;

    /**
     * @var string The currency of this product in an [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217) format
     *
     * @example EUR
     *
     * @Gedmo\Versioned
     * @Assert\Currency
     * @Groups({"read","write"})
     * @ORM\Column(type="string", nullable=true)
     */
    private $priceCurrency;

    /**
     * @var array A list of total taxes
     *
     * @example EUR
     *
     * @Groups({"read"})
     * @ORM\Column(type="array")
     */
    private $taxes = [];

    /**
     * @var DateTime The moment this request was created by the submitter
     *
     * @example 20190101
     *
     * @Groups({"read"})
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateCreated;

    /**
     * @var DateTime The moment this request was created by the submitter
     *
     * @example 20190101
     *
     * @Groups({"read"})
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateModified;

    /**
     * @var string The order of this invoice
     *
     * @example https://www.example.org/order/1
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true, name="order_uri")
     */
    private $order;

    /**
     * @var Payment The payments of this Invoice
     *
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\Payment", mappedBy="invoice", cascade={"persist"})
     * @MaxDepth(1)
     */
    private $payments;

    /**
     * @var Customer The customer this invoice relates to
     *
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer", inversedBy="invoices")
     * @MaxDepth(1)
     */
    private $customer;
    /**
     * @var string url of payment
     *
     * @Groups({"read"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $paymentUrl;

    /**
     * @var string redirect url after payment
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $redirectUrl;

    /**
     * @var string id of payment
     *
     * @Groups({"read"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $paymentId;

    /**
     * @var string status of invoice
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @var string Remarks on this invoice
     *
     * @Groups({"read","write"})
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $remark;

    /**
     * @var string Indicator whether the invoice is paid or not
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $paid = false;

    /**
     * @var Service The chosen payment service for the invoice
     *
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity=Service::class, inversedBy="invoices")
     * @MaxDepth(1)
     */
    private $service;

    /**
     * @var Subscription The subscription this invoice is made for
     *
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity=Subscription::class, inversedBy="invoices")
     * @MaxDepth(1)
     */
    private $subscription;

    /**
     *  @ORM\PrePersist
     *  @ORM\PreUpdate
     *
     *  */
    public function prePersist()
    {
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        /*@todo we should support non euro */
        $price = new Money(0, new Currency('EUR'));
        $taxes = [];

        foreach ($this->items as $item) {

            // Calculate Invoice Price
            //
            if (is_string($item->getPrice())) {
                //Value is a string, so presumably a float
                $float = floatval($item->getPrice());
                $float = $float * 100;
                $itemPrice = new Money((int) $float, new Currency($item->getPriceCurrency()));
            } else {
                // Calculate Invoice Price
                $itemPrice = new Money($item->getPrice(), new Currency($item->getPriceCurrency()));
            }

            $itemPrice = $itemPrice->multiply($item->getQuantity());
            $price = $price->add($itemPrice);

            // Calculate Taxes
            /*@todo we should index index on something else do, there might be diferend taxes on the same percantage. Als not all taxes are a percentage */
            foreach ($item->getTaxes() as $tax) {
                if (!array_key_exists($tax->getPercentage(), $taxes)) {
                    $tax[$tax->getPercentage()] = $itemPrice->multiply($tax->getPercentage() / 100);
                } else {
                    $taxPrice = $itemPrice->multiply($tax->getPercentage() / 100);
                    $tax[$tax->getPercentage()] = $tax[$tax->getPercentage()]->add($taxPrice);
                }
            }
        }

        $this->taxes = $taxes;
        $this->price = number_format($price->getAmount() / 100, 2, '.', '');
        $this->priceCurrency = $price->getCurrency();
    }

    public function getAllPaidPayments()
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('status', 'paid'));

        return $this->getPayments()->matching($criteria);
    }

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->payments = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getReferenceId(): ?int
    {
        return $this->reference;
    }

    public function setReferenceId(int $referenceId): self
    {
        $this->referenceId = $referenceId;

        return $this;
    }

//    public function getTargetOrganization(): ?string
//    {
//        return $this->targetOrganization;
//    }
//
//    public function setTargetOrganization(string $targetOrganization): self
//    {
//        $this->targetOrganization = $targetOrganization;
//
//        return $this;
//    }

    /**
     * @return Collection|InvoiceItem[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(InvoiceItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setInvoice($this);
        }

        return $this;
    }

    public function removeItem(InvoiceItem $item): self
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
            // set the owning side to null (unless already changed)
            if ($item->getInvoice() === $this) {
                $item->setInvoice(null);
            }
        }

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getDateCreated(): ?DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(DateTimeInterface $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateModified(): ?DateTimeInterface
    {
        return $this->dateModified;
    }

    public function setDateModified(DateTimeInterface $dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    public function getPriceCurrency(): ?string
    {
        return $this->priceCurrency;
    }

    public function setPriceCurrency(string $priceCurrency): self
    {
        $this->priceCurrency = $priceCurrency;

        return $this;
    }

    /**
     * @return array
     */
    public function getTaxes(): array
    {
        return $this->taxes;
    }

    public function getOrder(): ?string
    {
        return $this->order;
    }

    public function setOrder(?string $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setInvoice($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
            // set the owning side to null (unless already changed)
            if ($payment->getInvoice() === $this) {
                $payment->setInvoice(null);
            }
        }

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

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(string $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getPaymentUrl(): ?string
    {
        return $this->paymentUrl;
    }

    public function setPaymentUrl(string $paymentUrl): self
    {
        $this->paymentUrl = $paymentUrl;

        return $this;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function setRedirectUrl(string $redirectUrl): self
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId(string $paymentId): self
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): self
    {
        $this->remark = $remark;

        return $this;
    }

    public function getPaid(): ?bool
    {
        return $this->paid;
    }

    public function setPaid(?bool $paid): self
    {
        $this->paid = $paid;

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

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscription $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }
}
