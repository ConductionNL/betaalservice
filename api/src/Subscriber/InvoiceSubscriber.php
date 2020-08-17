<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Order;
use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

//use App\Entity\Request as CCRequest;

class InvoiceSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $serializer;
    private $nlxLogService;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->params = $params;
        $this->em = $em;
        $this->serializer = $serializer;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['newInvoice', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function newInvoice(ViewEvent $event)
    {
        $result = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $route = $event->getRequest()->attributes->get('_route');

        if (!$result instanceof Order || $route != 'api_invoices_post_collection') {
            return;
        }

        if (!$result->getReference()) {
            $organization = json_decode($event->getRequest()->getContent(), true)['organization'];
            $referenceId = $this->em->getRepository('App\Entity\Invoice')->getNextReferenceId($organization);
            $result->setReferenceId($referenceId);
            $organization = $this->commonGroundService->getResource($organization);
            if (array_key_exists('shortcode', $organization) && $organization['shortcode'] != null) {
                $shortcode = $organization['shortcode'];
            } else {
                $shortcode = $organization['name'];
            }
            $result->setReference($shortcode.'-'.date('Y').'-'.$referenceId);
        }

        return $result;
    }
}
