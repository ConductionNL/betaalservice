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

class StatusSubscriber implements EventSubscriberInterface
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
            KernelEvents::REQUEST => ['status', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    public function status(RequestEvent $event)
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

        if ($method != 'POST' || ($route != 'api_invoices_post_status_collection' || $post == null)) {
            return;
        }

        $needed = [
            'id',
        ];

        foreach ($needed as $requirement) {
            if (!array_key_exists($requirement, $post) || $post[$requirement] == null) {
                throw new BadRequestHttpException(sprintf('Compulsory property "%s" is not defined', $requirement));
            }
        }

        // invoice organization ip er vanuit gaan dat er een organisation object is meegeleverd
        $invoice = $this->em->getRepository(Invoice::class)->findOneBy(['id' => $post['id']]);

        if (!$invoice instanceof Invoice) {
            throw new BadRequestHttpException(sprintf('Invalid id'));
        }

        $service = $invoice->getService();

        $mollieService = new MollieService($service);
        var_dump($invoice->getPaymentId());
        $result = $mollieService->checkPayment($invoice->getPaymentId());

        $invoice->setStatus($result['status']);

        if ($result['paid']) {
            $invoice->setPaid(true);
        }

        $this->em->persist($invoice);
        $this->em->flush();

        $json = $this->serializer->serialize(
            $invoice,
            $renderType,
            ['enable_max_depth'=> true]
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
