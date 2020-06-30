<?php

namespace App\DataFixtures;

use App\Entity\Organization;
use App\Entity\Service;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AppFixtures extends Fixture
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
            strpos($this->params->get('app_domain'), 'huwelijksplanner.online') ||
            $this->params->get('app_domain') == 'huwelijksplanner.online' ||
            strpos($this->params->get('app_domain'), 'utrecht.commonground.nu') ||
            $this->params->get('app_domain') == 'utrecht.commonground.nu'
        ) {
            $organization = new Organization();
            $organization->setRsin('002220647');
            $organization->setShortCode('UT');
            $organization->setRedirectUrl($this->commonGroundService->cleanUrl('https://dev.huwelijksplanner.online/betalen/betaald'));
            $manager->persist($organization);

            $service = new Service();
            $service->setType('mollie');
            $service->setOrganization($organization);
            $service->setAuthorization('!changeMe!');
//        $service->
            $manager->persist($service);
            $manager->flush();
        }
        if (
            strpos($this->params->get('app_domain'), 'zuid-drecht.nl') ||
            $this->params->get('app_domain') == 'zuid-drecht.nl'
            ) {
            $organization = new Organization();
            $organization->setRsin('99999999');
            $organization->setShortCode('ZD');
            $organization->setRedirectUrl($this->commonGroundService->cleanUrl('https://zuid-drecht.nl/betalen/betaald'));
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
