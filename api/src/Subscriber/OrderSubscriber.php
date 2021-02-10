<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\Organization;
use App\Entity\Payment;
use App\Entity\Service;
use App\Entity\Tax;
use App\Service\MollieService;
use App\Service\SumUpService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
//        $result = $event->getControllerResult();
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
            'url',
            'mollieKey',
            'redirectUrl',

        ];

        foreach ($needed as $requirement) {
            if (!array_key_exists($requirement, $post) || $post[$requirement] == null) {
                throw new BadRequestHttpException(sprintf('Compulsory property "%s" is not defined', $requirement));
            }
        }

        $order = $this->commonGroundService->getResource($post['url']);
        $mollieKey = $post['mollieKey'];
        $redirectUrl = $post['redirectUrl'];

        $invoice = new Invoice();

        if (array_key_exists('reference', $order) && $order['reference']) {
            $invoice->setName($order['reference']);
        }
        if (array_key_exists('description', $order) && $order['description']) {
            $invoice->setDescription($order['description']);
        }
        if (array_key_exists('remark', $order) && $order['remark'] != null) {
            $invoice->setRemark($order['remark']);
        }
        if (array_key_exists('customer', $order) && $order['customer'] != null) {
            $invoice->setCustomer($order['customer']);
        }
        $this->em->persist($invoice);
        $this->em->flush();
        if (array_key_exists('items', $order) && $order['items'] != null && $order['items'] > 0) {
            foreach ($order['items'] as $item) {
                $invoiceItem = new InvoiceItem();
                $invoiceItem->setName($item['name']);
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
                $this->em->persist($invoice);
            }
            $this->em->flush();
        }
        $invoice->setOrder($order['@id']);

        // invoice organization ip er vanuit gaan dat er een organisation object is meegeleverd
        $organization = $this->em->getRepository('App:Organization')->findOrCreateByRsin($order['organization']);

        if (!($organization instanceof Organization)) {
            // invoice targetOrganization ip er vanuit gaan dat er een organisation object is meegeleverd
            $organization = new Organization();
            $organization->setRsin($order['organization']);
            if (array_key_exists('organization', $order) && array_key_exists('shortCode', $order['organization'])) {
                $organization->setShortCode($order['organization']['shortCode']);
            }
        }

        $organization->setRedirectUrl($redirectUrl);
        $service = new Service();
        $service->setAuthorization($mollieKey);
        $service->setOrganization($organization);
        $service->setType('mollie');

        $invoice->setPrice($order['price']);
        $invoice->setPriceCurrency($order['priceCurrency']);
        $invoice->setOrganization($organization);
        $invoice->setTargetOrganization($order['organization']);
        $invoice->setService($service);

//        $invoiceItem = new InvoiceItem();
//        $invoiceItem->setName($order['reference']);
//        $invoiceItem->setPrice($order['price']);
//        $invoiceItem->setPriceCurrency($order['priceCurrency']);
//        $invoiceItem->setQuantity(1);
//        $invoice->addItem($invoiceItem);

        /*
        if (array_key_exists('items', $order)) {
            foreach ($order['items'] as $item) {
                $invoiceItem = new InvoiceItem();
                $invoiceItem->setName($item['name']);
                $invoiceItem->setDescription($item['description']);
                $invoiceItem->setPrice($item['price']);
                $invoiceItem->setPriceCurrency($item['priceCurrency']);
                $invoiceItem->setOffer($item['offer']);
                $invoiceItem->setQuantity($item['quantity']);
                $invoice->addItem($invoiceItem);

                foreach ($item['taxes'] as $taxPost) {
                    $tax = new Tax();
                    $tax->setName($taxPost['name']);
                    $tax->setDescription($taxPost['description']);
                    $tax->setPrice($taxPost['price']);
                    $tax->setPriceCurrency($taxPost['priceCurrency']);
                    $tax->setPercentage($taxPost['percentage']);
                    $invoiceItem->addTax($tax);
                }
            }
        }
        */

        // Lets throw it in the db
        $this->em->persist($organization);
        $this->em->persist($service);
        $this->em->persist($invoice);
        $this->em->flush();
        $orderUpdate = [];
        $orderUpdate['invoice'] = $this->commonGroundService->cleanUrl(['component' => 'bc', 'type' => 'invoices', 'id' => $invoice->getId()]);
        $order = $this->commonGroundService->updateResource($orderUpdate, $invoice->getOrder());

        // recalculate all the invoice totals
        $invoice->calculateTotals();

        // Only create payment links if a payment service is configured
        if (
            (!$paymentService = $invoice->getService()) &&
            $invoice->getOrganization() != null
        ) {
            $paymentService = $invoice->getService();
        }
        if (isset($paymentService)) {
            switch ($paymentService->getType()) {
                case 'mollie':
                    $mollieService = new MollieService($paymentService);
                    $payment = $mollieService->createPayment($invoice, $event->getRequest());
                    $invoice->setPaymentUrl($payment['checkOutUrl']);
                    $invoice->setPaymentId($payment['mollieId']);
                    $this->em->persist($invoice);
                    $this->em->flush();
                    break;
                case 'sumup':
                    $sumupService = new SumUpService($paymentService);
                    $paymentUrl = $sumupService->createPayment($invoice);
                    $invoice->setPaymentUrl($paymentUrl);
                    break;
            }
        }

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
    }
}
