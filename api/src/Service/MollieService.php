<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\Payment;
use App\Entity\Service;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Symfony\Component\HttpFoundation\Request;

class MollieService
{
    private $mollie;
    private $serviceId;
    private $service;
    private $commonGroundService;
    private $em;

    public function __construct(Service $service, CommonGroundService $commonGroundService, EntityManagerInterface $em)
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

    public function createSubscriptionPayment(Invoice $invoice)
    {
        $customer = $this->commonGroundService->getResource($invoice->getCustomer());

        $currency = $invoice->getPriceCurrency();
        $amount = '' . $invoice->getPrice();
        $description = $invoice->getDescription();
        $redirectUrl = $invoice->getRedirectUrl();

        $customerMollie = $this->mollie->customers->create([
            'name' => $customer['name']
        ]);

        $invoice->setPaymentCustomerId($customerMollie->id);
        $this->em->persist($invoice);
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

    public function createSubscription(Invoice $invoice)
    {
        $invoiceItem = $invoice->getItems()->first();
        $interval = $this->commonGroundService->getResource($invoiceItem->getOffer())['recurrence'];

        if ($interval == "P30D" || $interval == "P1M") {
            $interval = "1 month";
        } elseif ($interval == "P1Y" || $interval == "P12M") {
            $interval = "12 months";
        }

        $paymentCustomer = $this->mollie->customers->get($invoice->getPaymentCustomerId());

        $subscription = $this->mollie->subscriptions->createFor($paymentCustomer, [
            'interval' => $interval,
            'amount' => [
                'currency' => $invoiceItem->getPriceCurrency(),
                'value' => $invoiceItem->getPrice(),
            ],
            'description' => $invoice->getDescription(),
        ]);

        $invoice->setSubscriptionId($subscription->id);
        $this->em->persist($invoice);
        $this->em->flush();

    }
}
