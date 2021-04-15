<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Invoice;
use App\Entity\Order;
use App\Service\MollieService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

//use App\Entity\Request as CCRequest;

class InvoiceStatusSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $serializer;
    private $nlxLogService;
    private $commonGroundService;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SerializerInterface $serializer, CommonGroundService $commonGroundService)
    {
        $this->params = $params;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->commonGroundService = $commonGroundService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['invoiceStatus', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function invoiceStatus(ViewEvent $event)
    {
        $invoice = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $route = $event->getRequest()->attributes->get('_route');

        if (!$invoice instanceof Invoice || $method != 'GET') {
            return;
        }
        $service = $invoice->getService();

        $mollieService = new MollieService($this->commonGroundService, $this->em, $service);

        $resultFromMollie = $mollieService->checkPayment($invoice->getPaymentId());

        $invoice->setStatus($resultFromMollie['status']);

        if ($resultFromMollie['paid']) {
            $invoice->setPaid(true);
        }

        $this->em->persist($invoice);
        $this->em->flush();


        return $invoice;
    }
}
