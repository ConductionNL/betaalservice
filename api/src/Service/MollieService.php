<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\Payment;
use App\Entity\Service;
use App\Entity\Subscription;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Psr7;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MollieService
{
    private $mollie;
    private $serviceId;
    private $service;
    private $commonGroundService;
    private $em;

    public function __construct(CommonGroundService $commonGroundService, EntityManagerInterface $em, Service $service = null)
    {
        $this->mollie = new MollieApiClient();
        $this->serviceId = $service->getId();
        $this->service = $service;
        $this->commonGroundService = $commonGroundService;
        $this->em = $em;


        try {
            $this->mollie->setApiKey($service->getAuthorization());
        } catch (ApiException $e) {
            // echo '<section><h2>Error: could not authenticate with Mollie API</h2><pre>'.$e->getMessage().'</pre></section>';
        }
    }

    public function createPayment(Invoice $invoice)
    {
        if ($invoice->getPrice() > 0.00) {
            $currency = $invoice->getPriceCurrency();
            $amount = '' . $invoice->getPrice();
            $description = $invoice->getDescription();
            $redirectUrl = $invoice->getRedirectUrl();

            try {
                $molliePayment = $this->mollie->payments->create([
                    'amount' => [
                        'currency' => $currency,
                        'value' => $amount,
                    ],
                    'description' => $description,
                    'redirectUrl' => $redirectUrl,
                    'metadata' => [
                        'order_id' => $invoice->getReference(),
                    ],
                ]);
                $object['checkOutUrl'] = $molliePayment->getCheckoutUrl();
                $object['mollieId'] = $molliePayment->id;

                return $object;
            } catch (ApiException $e) {
                return '<section><h2>Could not connect to payment provider</h2>' . $e->getMessage() . '</section>';
            }
        }

        return $this->service->getOrganization()->getRedirectUrl() . '/' . $invoice->getId();
    }

    public function updatePayment(string $paymentId, Service $service): ?Payment
    {
        $molliePayment = $this->mollie->payments->get($paymentId);
        $payment = $this->em->getRepository('App:Payment')->findOneBy(['paymentId' => $paymentId]);
        if ($payment instanceof Payment) {
            $payment->setStatus($molliePayment->status);

            return $payment;
        } else {
            $invoiceReference = $molliePayment->metadata->order_id;

            $invoice = $this->em->getRepository('App:Invoice')->findBy(['reference' => $invoiceReference]);

            if (is_array($invoice)) {
                $invoice = end($invoice);
            }
            if ($invoice instanceof Invoice) {
                $payment = new Payment();
                $payment->setPaymentId($molliePayment->id);
                $payment->setPaymentProvider($service);
                $payment->setStatus($molliePayment->status);
                $payment->setInvoice($invoice);
                $this->em->persist($payment);
                $this->em->flush();

                return $payment;
            }
        }

        return null;
    }

    public function checkPayment(string $paymentId)
    {
        $payment = $this->mollie->payments->get($paymentId);
        $object['status'] = $payment->status;
        $object['paid'] = $payment->isPaid();

        return $object;
    }

    public function getCustomer($customerId)
    {
        return $this->mollie->customers->get($customerId);
    }

    public function createCustomer($customer)
    {
        $customerMollie = $this->mollie->customers->create([
            'name' => $customer->getName(),
            'metadata' => [
                'customerUrl' => $customer->getCustomerUrl()
            ]
        ]);

        return $customerMollie;
    }

    public function updateSubscription(Subscription $subscription, $invoiceItemsArray)
    {
        $newPrice = 0;
        $offerUrls = [];
        foreach ($invoiceItemsArray as $array) {
            foreach ($array as $item) {
                if (!in_array($item->getOffer(), $offerUrls)) {
                    $newPrice += ($item->getQuantity() * $item->getPrice());
                    $offerUrls[] = $item->getOffer();
                }
            }
        }

        $newPrice = ((string) $newPrice) . '.00';

        $customer = $this->mollie->customers->get($subscription->getCustomer()->getCustomerId());

        $subscriptionFromMollie = $customer->getSubscription($subscription->getSubscriptionId());
        $subscriptionFromMollie->amount = (object)[
            "currency" => $invoiceItemsArray[0][0]->getPriceCurrency(),
            "value" => $newPrice,
        ];

        $updatedSubscription = $subscriptionFromMollie->update();

        $subscription->setSubscriptionId($updatedSubscription->id);
        $this->em->persist($subscription);
        $this->em->flush();
    }

    public function createSubscriptionPayment(Invoice $invoice)
    {
        if ($invoice->getCustomer()->getCustomerId() == null) {
            $customerMollie = $this->createCustomer($invoice->getCustomer());
        } else {
            $customerMollie = $this->getCustomer($invoice->getCustomer()->getCustomerId());
        }

        $subscription = new Subscription();
        $subscription->addInvoice($invoice);
        $subscription->setCustomer($invoice->getCustomer());
        $subscription->setService($invoice->getService());
        $this->em->persist($subscription);
        $this->em->flush();

        $currency = $invoice->getPriceCurrency();
        $amount = '' . $invoice->getPrice();
        $description = $invoice->getDescription();
        $redirectUrl = $invoice->getRedirectUrl();

        $invoice->getCustomer()->setCustomerId($customerMollie->id);
        $customer = $invoice->getCustomer();
        $this->em->persist($invoice);
        $this->em->persist($customer);
        $this->em->flush();

        $molliePayment = $this->mollie->payments->create([
            'amount' => [
                'currency' => $currency,
                'value' => $amount,
            ],
            'description' => $description,
            'redirectUrl' => $redirectUrl,
            'metadata' => [
                'order_id' => $invoice->getReference(),
            ],
            'customerId' => $customerMollie->id,
            'sequenceType' => 'first'
        ]);

        $object['checkOutUrl'] = $molliePayment->getCheckoutUrl();
        $object['mollieId'] = $molliePayment->id;

        return $object;
    }

    public function getSubscription($customerId, $subscriptionId)
    {
        $customer = $this->mollie->customers->get($customerId);
        $subscription = $this->mollie->subscriptions->getFor($customer, $subscriptionId);

        return $subscription;
    }

    public function createSubscription(Invoice $invoice)
    {
        $invoiceItem = $invoice->getItems()->first();
        $interval = $this->commonGroundService->getResource($invoiceItem->getOffer())['recurrence'];

        if (!isset($interval)) {
            throw new BadRequestHttpException('Recurrence of invoice\'s offer not set or not found');
        }

        if ($interval == "P30D" || $interval == "P1M") {
            $interval = "1 month";
        } elseif ($interval == "P1Y" || $interval == "P12M") {
            $interval = "12 months";
        }

        $paymentCustomer = $this->mollie->customers->get($invoice->getCustomer()->getCustomerId());

        $subscriptionFromMollie = $this->mollie->subscriptions->createFor($paymentCustomer, [
            'interval' => $interval,
            'amount' => [
                'currency' => $invoiceItem->getPriceCurrency(),
                'value' => $invoiceItem->getPrice(),
            ],
            'description' => $invoice->getDescription(),
        ]);

        $subscription = $invoice->getSubscription();
        $subscription->setSubscriptionId($subscriptionFromMollie->id);
        $this->em->persist($subscription);
        $this->em->flush();
    }
}
