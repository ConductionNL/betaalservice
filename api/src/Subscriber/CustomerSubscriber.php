<?php
// src/EventListener/CustomerSubscriber.php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Customer;
use App\Entity\Invoice;
use App\Service\MollieService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class CustomerSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $serializer;
    private $client;
    private $commonGroundService;
// this method can only return the event names; you cannot define a
// custom method name to execute when each event triggers

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
//            KernelEvents::REQUEST => ['createCustomerForMollie', EventPriorities::POST_DESERIALIZE],
            KernelEvents::VIEW => ['getCustomerFromMollie', EventPriorities::PRE_SERIALIZE],
        ];
    }

//    public function createCustomerForMollie(RequestEvent $event)
//    {
//        $customer = $event->getControllerResult();
//        $method = $event->getRequest()->getMethod();
//        $route = $event->getRequest()->attributes->get('_route');
//
//        if (!$customer instanceof Customer || $method != 'POST') {
//            return;
//        }
//
//        $mollieService = new MollieService($this->commonGroundService, $this->em);
//
//        if ($customer->getCustomerId() == null) {
//            $customerMollie = $mollieService->createCustomer($customer->getCustomerUrl());
//
//            $customer->setCustomerId($customerMollie->id);
//            $customer->setCustomerFromService((array)$customerMollie);
//            $this->em->persist($customer);
//            $this->em->flush();
//        }
//
//    }

    public function getCustomerFromMollie(ViewEvent $event)
    {
//        $customer = $event->getControllerResult();
//        $method = $event->getRequest()->getMethod();
//        $route = $event->getRequest()->attributes->get('_route');
//
//        if (!$customer instanceof Customer || $method != 'GET') {
//            return;
//        }
//        $mollieService = new MollieService($this->commonGroundService, $this->em);
//
//        $customerMollie = $mollieService->getCustomer($customer->getCustomerUrl());
//
//        if (isset($customerMollie)) {
//            $customer->setCustomerFromService((array)$customerMollie);
//            $this->em->persist($customer);
//            $this->em->flush();
//        }
    }
}
