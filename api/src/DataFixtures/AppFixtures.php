<?php

namespace App\DataFixtures;

use App\Entity\Organization;
use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AppFixtures extends Fixture
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function load(ObjectManager $manager)
    {
        // Lets make sure we only run these fixtures on larping enviroment
        if (strpos($this->params->get('app_domain'), "huwelijksplanner.online") == false && $this->params->get('app_domain') != 'huwelijksplanner.online') {
            return false;
        }

        $organization = new Organization();
        $organization->setRsin('002220647');
        $organization->setShortCode('UT');
        $organization->setRedirectUrl('https://huwelijksplanner.online/betalen/betaald');
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
