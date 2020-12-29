<?php

namespace App\DataFixtures;

use App\Entity\Organization;
use App\Entity\Service;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LarpingFixtures extends Fixture
{
    private $params;
    private $commonGroundService;

    public function __construct(ParameterBagInterface $params, CommonGroundService $commonGroundService)
    {
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;
    }

    public function load(ObjectManager $manager)
    {
        // Lets make sure we only run these fixtures on larping enviroment
        if (
            !$this->params->get('app_build_all_fixtures') &&
            $this->params->get('app_domain') != 'larping.eu' && strpos($this->params->get('app_domain'), 'larping.eu') == false
        ) {
            $organization = new Organization();
            $organization->setRsin('00000000');
            $organization->setShortCode('LR');
            $organization->setRedirectUrl($this->commonGroundService->cleanUrl('https://dev.larping.eu/order/payment'));
            $manager->persist($organization);

            $service = new Service();
            $service->setType('mollie');
            $service->setOrganization($organization);
            $service->setAuthorization('!changeMe!');
//        $service->
            $manager->persist($service);
            $manager->flush();
        }
    }
}
