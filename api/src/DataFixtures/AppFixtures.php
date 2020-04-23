<?php

namespace App\DataFixtures;

use App\Entity\Organization;
use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $params;
    private $encoder;

    public function __construct(ParameterBagInterface $params, UserPasswordEncoderInterface $encoder)
    {
        $this->params = $params;
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        // Lets make sure we only run these fixtures on larping enviroment
        if (strpos($this->params->get('app_domain'), "huwelijksplanner.online") == false && $this->params->get('app_domain' != 'huwelijksplanner.online')) {
            return false;
        }

        $organization = new Organization();
        $organization->setRsin('002220647');
        $organization->setShortCode('UT');
        $organization->setRedirectUrl('https://huwelijksplanner.online/betalen/betaald');
        $manager->persist($organization);

        $service = new Service();
        $service->setType('mollie');
        $service->setOrganization($organization)
//        $service->
        $manager->persist($service);
        $manager->flush();
    }
}
