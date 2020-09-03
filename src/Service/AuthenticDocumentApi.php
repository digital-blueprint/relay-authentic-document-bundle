<?php

declare(strict_types=1);
/**
 * Egiz Image API service.
 */

namespace DBP\API\AuthenticDocumentBundle\Service;


use DBP\API\CoreBundle\Service\PersonProviderInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentRequest;
use DBP\API\AuthenticDocumentBundle\Message\AuthenticDocumentRequestMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class AuthenticDocumentApi
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

    public function createAuthenticDocumentRequestMessage(AuthenticDocumentRequest $authenticImageRequest)
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

        $this->bus->dispatch(new AuthenticDocumentRequestMessage($person, $date), [
            // wait 5 seconds before processing
            new DelayStamp($seconds * 1000)
        ]);
    }
}
