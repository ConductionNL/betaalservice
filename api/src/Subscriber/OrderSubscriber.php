<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\Organization;
use App\Entity\Payment;
use App\Entity\Service;
use App\Entity\Subscription;
use App\Entity\Tax;
use App\Service\MollieService;
use App\Service\SumUpService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class OrderSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $serializer;
    private $client;
    private $commonGroundService;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SerializerInterface $serializer, CommonGroundService $commonGroundService)
    {
        $this->params = $params;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->client = new Client();
        $this->commonGroundService = $commonGroundService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['invoice', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    public function invoice(RequestEvent $event)
    {
        try {
            $method = $event->getRequest()->getMethod();
            $route = $event->getRequest()->attributes->get('_route');
            $post = json_decode($event->getRequest()->getContent(), true);

            $contentType = $event->getRequest()->headers->get('accept');
            if (!$contentType) {
                $contentType = $event->getRequest()->headers->get('Accept');
            }

            switch ($contentType) {
                case 'application/json':
                    $renderType = 'json';
                    break;
                case 'application/ld+json':
                    $renderType = 'jsonld';
                    break;
                case 'application/hal+json':
                    $renderType = 'jsonhal';
                    break;
                default:
                    $contentType = 'application/json';
                    $renderType = 'json';
            }

            if ($method != 'POST' || ($route != 'api_invoices_post_order_collection' || $post == null)) {
                return;
            }

            $needed = [
                'orderUrl',
                'redirectUrl'
            ];

            foreach ($needed as $requirement) {
                if (!array_key_exists($requirement, $post) || $post[$requirement] == null) {
                    throw new BadRequestHttpException(sprintf('Compulsory property "%s" is not defined', $requirement));
                }
            }

            // Get actual order from given @id
            $order = $this->commonGroundService->getResource($post['orderUrl']);

            // If there is no Service for the offering organization exit
            $serviceRepository = $this->em->getRepository(Service::class);
            $service = $serviceRepository->findOneBy([
                'organization' => $order['organization']
            ]);
            if (!isset($service)) {
                throw new BadRequestHttpException('No service found for given organization');
            }

            // Check if there is already a invoice for this order if this is not a subscription
            if ((isset($post['paymentType']) && $post['paymentType'] !== 'subscription') || !isset($post['paymentType'])) {
                $invoiceRepository = $this->em->getRepository(Invoice::class);
                $invoices = $invoiceRepository->findBy([
                    'order' => $order['@id']
                ]);
                if (isset($invoices)) {
                    $highestTimestamp = 0;
                    foreach ($invoices as $invoice) {
                        if ($invoice->getDateCreated()->getTimestamp() > $highestTimestamp) {
                            $highestTimestamp = $invoice->getDateCreated()->getTimestamp();
                            $latestInvoice = $invoice;
                        }
                    }
                }
            }

            // If there is no Customer with the customer from the order create a Customer
            $customerRepository = $this->em->getRepository(Customer::class);
            $customer = $customerRepository->findOneBy([
                'customerUrl' => $order['customer']
            ]);
            if (!isset($customer)) {
                $customerFromCommonground = $this->commonGroundService->getResource($order['customer']);
                $customer = new Customer();
                $customer->setName($customerFromCommonground['name']);
                $customer->setCustomerUrl($customerFromCommonground['@id']);
                $customer->setService($service);
                $this->em->persist($customer);
            }
            $invoice = [];
            if (isset($latestInvoice)) {
                $invoice = $latestInvoice;
                $invoice->setRedirectUrl($post['redirectUrl'] . '?invoiceUrl=' . $invoice->getId());
            } else {
                $invoice = $this->createInvoiceFromOrder($order, $post['redirectUrl']);
            }
            $invoice->setService($service);
            $invoice->setCustomer($customer);
            $this->em->persist($invoice);
            $this->em->flush();

            unset($order['items']);
            $this->commonGroundService->updateResource($order, ['component' => 'orc', 'type' => 'orders', 'id' => $order['id']]);

            $order['invoice'] = $this->commonGroundService->cleanUrl(['component' => 'bc', 'type' => 'invoices', 'id' => $invoice->getId()]);
            // recalculate all the invoice totals
            $invoice->calculateTotals();

            if ($invoice->getService() != null) {
                $service = $invoice->getService();
                switch ($service->getType()) {
                    case 'mollie':
                        $mollieService = new MollieService($this->commonGroundService, $this->em, $service);
                        if (isset($post['paymentType']) && $post['paymentType'] == 'subscription' &&
                            isset($post['accumulateSubscription']) && $post['accumulateSubscription'] == true) {
                            $subscriptionRepo = $this->em->getRepository(Subscription::class);
                            $subscription = $subscriptionRepo->findOneBy(array(
                                'organization' => $order['organization'],
                                'customer' => $customer
                            ));

                            if (isset($subscription)) {
                                // Update subscription
                                $payment = $mollieService->updateSubscription($subscription, $order['items']);
                            } else {
                                // Make subscription payment
                                $payment = $mollieService->createSubscriptionPayment($invoice);
                            }
                        } elseif (isset($post['paymentType']) && $post['paymentType'] == 'subscription') {
                            // Make subscription payment
                            $payment = $mollieService->createSubscriptionPayment($invoice);
                        } else {
                            // Make normal payment
                            $payment = $mollieService->createPayment($invoice);
                        }
                        $invoice->setPaymentUrl($payment['checkOutUrl']);
                        $invoice->setPaymentId($payment['mollieId']);
                        $this->em->persist($invoice);
                        break;
                    case 'sumup':
                        $sumupService = new SumUpService($invoice->getService());
                        $paymentUrl = $sumupService->createPayment($invoice);
                        $invoice->setPaymentUrl($paymentUrl);
                        break;
                }
            }

            $this->em->flush();

            $json = $this->serializer->serialize(
                $invoice,
                $renderType,
                ['enable_max_depth' => true]
            );

            // Creating a response
            $response = new Response(
                $json,
                Response::HTTP_CREATED,
                ['content-type' => $contentType]
            );

            $event->setResponse($response);

            return $invoice;

        } catch (\Exception $e) {
            $json = $this->serializer->serialize(
                $e->getMessage(),
                $renderType,
                ['enable_max_depth' => true]
            );

            // Creating a response
            $response = new Response(
                $json,
                Response::HTTP_CREATED,
                ['content-type' => $contentType]
            );
            $event->setResponse($response);

            return $e->getMessage();
        }
    }

    public function createInvoiceFromOrder($order, $redirectUrl)
    {
        $invoice = new Invoice();
        $invoice->setRedirectUrl($redirectUrl . '?invoiceId=' . $invoice->getId());

        if (array_key_exists('name', $order) && $order['name']) {
            $invoice->setName($order['name']);
        }
        if (array_key_exists('description', $order) && $order['description']) {
            $invoice->setDescription($order['description']);
        }
        if (array_key_exists('remark', $order) && $order['remark'] != null) {
            $invoice->setRemark($order['remark']);
        }
        if (array_key_exists('organization', $order) && $order['organization'] != null) {
            $invoice->setOrganization($order['organization']);
        }
        if (array_key_exists('price', $order) && $order['price'] != null) {
            $invoice->setPrice($order['price']);
        }
        if (array_key_exists('priceCurrency', $order) && $order['priceCurrency'] != null) {
            $invoice->setPriceCurrency($order['priceCurrency']);
        }
        $this->em->persist($invoice);

        if (array_key_exists('items', $order) && $order['items'] != null && $order['items'] > 0) {
            foreach ($order['items'] as $item) {
                $invoiceItem = new InvoiceItem();
                if (array_key_exists('name', $item) && $item['name'] != null) {
                    $invoiceItem->setName($item['name']);
                }
                if (array_key_exists('description', $item) && $item['description'] != null) {
                    $invoiceItem->setName($item['description']);
                }
                if (array_key_exists('offer', $item) && $item['offer'] != null) {
                    $invoiceItem->setOffer($item['offer']);
                }
                if (array_key_exists('quantity', $item) && $item['quantity'] != null) {
                    $invoiceItem->setQuantity($item['quantity']);
                }
                if (array_key_exists('price', $item) && $item['price'] != null) {
                    $invoiceItem->setPrice($item['price']);
                }
                if (array_key_exists('priceCurrency', $item) && $item['priceCurrency'] != null) {
                    $invoiceItem->setPriceCurrency($item['priceCurrency']);
                }
                $invoice->addItem($invoiceItem);
            }
        }
        $invoice->setOrder($order['@id']);
        $invoice->setRedirectUrl($redirectUrl . '?invoiceId=' . $invoice->getId());
        $this->em->persist($invoice);

        return $invoice;
    }
}
