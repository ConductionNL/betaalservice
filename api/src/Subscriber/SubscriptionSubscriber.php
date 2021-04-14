<?php
// src/EventListener/SubscriptionSubscriber.php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Subscription;
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

class SubscriptionSubscriber implements EventSubscriberInterface
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
//            KernelEvents::REQUEST => ['createSubscriptionForMollie', EventPriorities::POST_DESERIALIZE],
            KernelEvents::VIEW => ['getSubscriptionFromMollie', EventPriorities::PRE_VALIDATE],
        ];
    }

//    public function createSubscriptionForMollie(RequestEvent $event)
//    {
//        $subscription = $event->getControllerResult();
//        $method = $event->getRequest()->getMethod();
//        $route = $event->getRequest()->attributes->get('_route');
//
//        if (!$subscription instanceof Subscription || $method != 'POST') {
//            return;
//        }
//
//        $mollieService = new MollieService($this->commonGroundService, $this->em);
//
//        if ($subscription->getSubscriptionId() == null) {
//            $subscriptionMollie = $mollieService->createSubscription($subscription->getSubscriptionUrl());
//
//            $subscription->setSubscriptionId($subscriptionMollie->id);
//            $subscription->setSubscriptionFromService((array)$subscriptionMollie);
//            $this->em->persist($subscription);
//            $this->em->flush();
//        }
//
//    }

    public function getSubscriptionFromMollie(ViewEvent $event)
    {
        $subscription = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $route = $event->getRequest()->attributes->get('_route');

        if (!$subscription instanceof subscription || $method != 'GET' && $subscription->getCustomer() != null &&
            $subscription->getCustomer()->getCustomerId() != null && $subscription->getSubscriptionId() != null) {
            return;
        }
        $mollieService = new MollieService($this->commonGroundService, $this->em);

        $subscriptionMollie = $mollieService->getSubscription($subscription->getCustomer()->getCustomerId(), $subscription->getSubscriptionId());

        if (isset($subscriptionMollie)) {
            $subscription->setSubscriptionFromService((array)$subscriptionMollie);
            $this->em->persist($subscription);
            $this->em->flush();
        }
    }
}
