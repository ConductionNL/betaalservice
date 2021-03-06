<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Invoice;
use App\Service\MollieService;
use App\Service\SumUpService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class PaymentCreationSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $serializer;
    private $client;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->params = $params;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->client = new Client();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['payment', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function payment(ViewEvent $event)
    {
        $result = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $route = $event->getRequest()->attributes->get('_route');

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
        switch ($method) {
            case 'POST':
                $statusCode = Response::HTTP_CREATED;
                break;
            case 'DELETE':
                $statusCode = Response::HTTP_NO_CONTENT;
                break;
            default:
                $statusCode = Response::HTTP_OK;
                break;
        }
        if ($result instanceof Invoice && $method != 'DELETE') {
//            if (
//                (!$paymentService = $result->getService()) &&
//                $result->getOrganization() != null &&
//                count($result->getOrganization()->getServices()) > 0
//            ) {
//                $paymentService = $result->getOrganization()->getServices()[0];
//            }
//            if (isset($paymentService)) {
//                switch ($paymentService->getType()) {
//                case 'mollie':
//                    $mollieService = new MollieService($paymentService);
//                    $paymentUrl = $mollieService->createPayment($result, $event->getRequest());
//                    $result->setPaymentUrl($paymentUrl['checkOutUrl']);
//                    $result->setPaymentId($paymentUrl['mollieId']);
//                    $this->em->persist($result);
//                    $this->em->flush();
//                    break;
//                case 'sumup':
//                    $sumupService = new SumUpService($paymentService);
//                    $paymentUrl = $sumupService->createPayment($result);
//                    $result->setPaymentUrl($paymentUrl);
//                    break;
//                }
//            }

            $json = $this->serializer->serialize(
                $result,
                $renderType,
                ['enable_max_depth'=> true]
            );

            // Creating a response
            $response = new Response(
                $json,
                $statusCode,
                ['content-type' => $contentType]
            );
            $event->setResponse($response);
        } else {
            return;
        }
    }
}
