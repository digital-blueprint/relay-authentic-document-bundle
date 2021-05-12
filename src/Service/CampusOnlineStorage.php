<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Service;

use DBP\API\AuthenticDocumentBundle\API\DocumentStorageException;
use DBP\API\AuthenticDocumentBundle\API\DocumentStorageInterface;
use DBP\API\AuthenticDocumentBundle\UCard\UCardAPI;
use DBP\API\AuthenticDocumentBundle\UCard\UCardException;
use DBP\API\AuthenticDocumentBundle\UCard\UCardType;
use DBP\API\CoreBundle\Entity\Person;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CampusOnlineStorage implements DocumentStorageInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var UCardAPI
     */
    private $api;

    private $setupDone;

    private $container;

    // FIXME: Force BA for testing purposes
    private const CARD_TYPE = UCardType::BA;

    public function __construct(UCardAPI $ucard, ContainerInterface $container)
    {
        $this->api = $ucard;
        $this->setupDone = false;
        $this->container = $container;
    }

    /**
     * @throws DocumentStorageException
     */
    private function setupApi(): UCardAPI
    {
        // NOTE: this doesn't work for long running processes, since the token will expire
        if (!$this->setupDone) {
            $config = $this->container->getParameter('dbp_api.authenticdocument.config');
            assert(is_array($config));
            $clientId = $config['co_oauth2_api_client_id'] ?? '';
            $clientSecret = $config['co_oauth2_api_client_secret'] ?? '';
            $baseUrl = $config['co_oauth2_api_api_url'] ?? '';

            $this->api->setBaseUrl($baseUrl);
            try {
                $this->api->fetchToken($clientId, $clientSecret);
            } catch (UCardException $e) {
                throw new DocumentStorageException($e->getMessage());
            }
            $this->setupDone = true;
        }

        return $this->api;
    }

    private function getIdentForPerson(Person $person): string
    {
        // FIXME
        return '5E5332FCA667E1D7';
    }

    public function storeDocument(Person $person, \DateTimeInterface $requestCreatedDate, string $documentType, string $documentData): void
    {
        // FIXME: check if we can handle $documentType
        $ident = $this->getIdentForPerson($person);
        $cardType = self::CARD_TYPE;
        $api = $this->setupApi();

        try {
            $cards = $api->getCardsForIdent($ident, $cardType);

            // If there exists no card of the specified type we have to create one
            if (count($cards) === 0) {
                $api->createCardForIdent($ident, $cardType);
                $cards = $api->getCardsForIdent($ident, $cardType);
            }
            assert(count($cards) !== 0 && $cards[0]->cardType === $cardType);
            $card = $cards[0];
            $api->setCardPicture($card, $documentData);
        } catch (UCardException $e) {
            throw new DocumentStorageException($e->getMessage());
        }
    }

    public function storeDocumentError(Person $person, \DateTimeInterface $requestCreatedDate, string $documentType, string $message): void
    {
        $this->logger->error('Failed: '.$message);
    }

    public function storeDocumentNotAvailable(Person $person, \DateTimeInterface $requestCreatedDate, string $documentType): void
    {
        $this->logger->error($documentType.' not available');
    }

    public function canStoreDocument(Person $person, string $documentType): bool
    {
        // FIXME: check if we can handle $documentType
        $ident = $this->getIdentForPerson($person);
        $cardType = self::CARD_TYPE;
        $api = $this->setupApi();

        try {
            $cards = $api->getCardsForIdent($ident, $cardType);
            // XXX: If there exists no card of the specified type we have to create one, otherwise we don't know
            // if we can create/update it
            if (count($cards) === 0) {
                $api->createCardForIdent($ident, $cardType);
                $cards = $api->getCardsForIdent($ident, $cardType);
            }
            assert(count($cards) !== 0 && $cards[0]->cardType === $cardType);
            $card = $cards[0];

            return $card->isUpdatable;
        } catch (UCardException $e) {
            throw new DocumentStorageException($e->getMessage());
        }
    }
}
