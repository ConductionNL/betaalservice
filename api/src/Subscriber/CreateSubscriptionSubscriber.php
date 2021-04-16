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

class CreateSubscriptionSubscriber implements EventSubscriberInterface
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

            if ($method != 'POST' || $route != 'api_invoices_post_create_subscription_collection') {
                return;
            }

            $needed = [
                'invoice'
            ];

            foreach ($needed as $requirement) {
                if (!array_key_exists($requirement, $post) || $post[$requirement] == null) {
                    throw new BadRequestHttpException(sprintf('Compulsory property "%s" is not defined', $requirement));
                }
            }

            $invoiceRepostiory = $this->em->getRepository(Invoice::class);
            $invoice = $invoiceRepostiory->findOneBy(['id' => $post['invoice']]);
            if ($invoice instanceof Invoice && $invoice->getPaid() == true && $invoice->getPaymentId() != null && $invoice->getSubscription() != null) {
                $subscription = $invoice->getSubscription();

                $mollieService = new MollieService($this->commonGroundService, $this->em, $invoice->getService());

                if ($invoice->getSubscription()->getSubscriptionId() != null) {
                    $subscription = $mollieService->updateSubscription($subscription, $invoice->getItems());
                } else {
                    $subscription = $mollieService->createSubscription($invoice);
                }

                $response = new Response(
                    json_encode((array)$subscription),
                    Response::HTTP_OK,
                    ['content-type' => 'application/json']
                );

                $event->setResponse($response);
            } else {
                return 'Invoice not found or not paid';
            }
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
        }
    }
}
