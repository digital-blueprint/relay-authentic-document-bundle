<?php

declare(strict_types=1);
/**
 * Egiz Image API service.
 */

namespace DBP\API\EgizImageBundle\Service;


use DBP\API\CoreBundle\Service\PersonProviderInterface;
use DBP\API\EgizImageBundle\Entity\AuthenticImageRequest;
use DBP\API\EgizImageBundle\Message\EgizImageRequest;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class EgizImageApi
{
    /**
     * @var MessageBusInterface
     */
    private $bus;

    /**
     * @var PersonProviderInterface
     */
    private $personProvider;


    public function __construct(MessageBusInterface $bus, PersonProviderInterface $personProvider)
    {
        $this->bus = $bus;
        $this->personProvider = $personProvider;
    }

    public function createAuthenticImageRequest(AuthenticImageRequest $authenticImageRequest)
    {
        $person = $this->personProvider->getCurrentPerson();

        // date we would get from egiz
        $date = new \DateTime();
        // add 5 sec for testing
        $date->add(new \DateInterval('PT5S'));

        $delayInterval = $date->diff(new \DateTime());
        $seconds = $delayInterval->days * 86400 + $delayInterval->h * 3600
            + $delayInterval->i * 60 + $delayInterval->s;
        dump($seconds);

        $this->bus->dispatch(new EgizImageRequest($person, $date), [
            // wait 5 seconds before processing
            new DelayStamp($seconds * 1000)
        ]);
    }
}
