<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Service;

use DateTimeImmutable;
use DBP\API\AuthenticDocumentBundle\API\DocumentStorageInterface;
use DBP\API\AuthenticDocumentBundle\DocumentHandler\AvailabilityStatus;
use DBP\API\AuthenticDocumentBundle\DocumentHandler\DocumentHandler;
use DBP\API\AuthenticDocumentBundle\DocumentHandler\DocumentIndexEntry;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocument;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentRequest;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentType;
use DBP\API\AuthenticDocumentBundle\Helpers\Tools;
use DBP\API\AuthenticDocumentBundle\Message\AuthenticDocumentRequestMessage;
use DBP\API\CoreBundle\API\PersonProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class AuthenticDocumentApi implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var MessageBusInterface
     */
    private $bus;

    /**
     * @var PersonProviderInterface
     */
    private $personProvider;

    /**
     * @var DocumentStorageInterface
     */
    private $storage;

    /**
     * @var DocumentHandler
     */
    private $documentHandler;

    public function __construct(
        MessageBusInterface $bus,
        LoggerInterface $logger,
        PersonProviderInterface $personProvider,
        DocumentStorageInterface $storage,
        DocumentHandler $documentHandler
    ) {
        $this->bus = $bus;
        $this->logger = $logger;
        $this->personProvider = $personProvider;
        $this->storage = $storage;
        $this->documentHandler = $documentHandler;
    }

    public function setConfig(array $config)
    {
        $this->documentHandler->setIDPUrl($config['dhandler_idp_url'] ?? '');
        $this->documentHandler->setHandlerUrl($config['dhandler_api_url'] ?? '');
    }

    /**
     * Creates Symfony message and dispatch delayed message to download a document from egiz in the future.
     */
    public function createAndDispatchAuthenticDocumentRequestMessage(
        AuthenticDocumentRequest $authenticDocumentRequest, string $token): AuthenticDocumentRequestMessage
    {
        $typeId = $authenticDocumentRequest->getTypeId();
        $dateCreated = $authenticDocumentRequest->getDateCreated();

        $entry = $this->getDocumentIndexEntry($typeId, $token);
        if ($entry === null || $entry->availabilityStatus === AvailabilityStatus::NOT_AVAILABLE) {
            throw new NotFoundHttpException('Document is not available');
        }

        // If it's already available there might be no eta, so just assume "now"
        $eta = $entry->eta ?? new DateTimeImmutable();

        // XXX: for demo/test purposes
        if ($_ENV['APP_DEPLOYMENT_ENV'] !== 'production' && $token === 'photo-jpeg-available-token') {
            return new AuthenticDocumentRequestMessage(
                null, 'foobar', $typeId, $dateCreated, $eta);
        }

        // we can decode the token here after if was proven valid by the request in getAuthenticDocumentType
        // note: it would also be possible to get the information from Keycloak directly but we don't want
        //       to be locked in into it and don't know if all data is available
        //       (https://auth-dev.tugraz.at/auth/realms/tugraz/broker/eid-oidc/token)
        $tokenInformation = $this->documentHandler->getUserInfo($token);
        $givenName = $tokenInformation->givenName;
        $familyName = $tokenInformation->familyName;
        $birthDay = $tokenInformation->birthDate;

        // try to match name and birthday to a person
        $people = $this->personProvider->getPersonsByNameAndBirthday($givenName, $familyName, new \DateTime($birthDay));
        $peopleCount = count($people);

        if ($peopleCount === 0) {
            throw new NotFoundHttpException("Person $givenName $familyName could not be found!");
        } elseif ($peopleCount > 1) {
            throw new NotFoundHttpException("Multiple people with name $givenName $familyName were found!");
        }

        $person = $people[0];

        // Before we queue anything we check if we can store it first
        if (!$this->storage->canStoreDocument($person, $typeId)) {
            throw new \Exception("Can't store");
        }

        $message = new AuthenticDocumentRequestMessage($person, $entry->documentToken, $typeId, $dateCreated, $entry->eta);
        $this->bus->dispatch(
            $message, [
            $this->getDelayStampFromDocumentIndexEntry($entry),
        ]);

        return $message;
    }

    protected function getDocumentIndexEntry($id, $token): ?DocumentIndexEntry
    {
        $entries = $this->documentHandler->getDocumentIndex($token);
        foreach ($entries as $key => $entry) {
            $entryId = self::getTypeIdForKey($key);
            if ($entryId !== null && $entryId === $id) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Handle Symfony Message AuthenticDocumentRequestMessage to download the document and store it.
     */
    public function handleRequestMessage(AuthenticDocumentRequestMessage $message)
    {
        $documentToken = $message->getDocumentToken();
        $typeId = $message->getTypeId();

        $entry = $this->getDocumentIndexEntry($typeId, $documentToken);

        if ($entry === null) {
            $this->storage->storeDocumentError(
                $message->getPerson(),
                $message->getRequestCreatedDate(),
                $typeId,
                'entry not found');

            return;
        }

        switch ($entry->availabilityStatus) {
            case AvailabilityStatus::AVAILABLE:
                try {
                    $data = $this->getAuthenticDocumentData($typeId, $documentToken);
                    $this->storage->storeDocument(
                        $message->getPerson(),
                        $message->getRequestCreatedDate(),
                        $typeId,
                        $data);
                } catch (\Exception $e) {
                    $this->storage->storeDocumentError(
                        $message->getPerson(),
                        $message->getRequestCreatedDate(),
                        $typeId,
                        $e->getMessage());
                }

                break;
            case AvailabilityStatus::REQUESTED:
                // if document is not yet available dispatch a new delayed message
                $newMessage = clone $message;
                $newMessage->incRetry();
                $newMessage->setEstimatedResponseDate($entry->eta);

                // wait at least 60 sec
                $this->bus->dispatch($newMessage, [
                    $this->getDelayStampFromDocumentIndexEntry($entry, 60),
                ]);
                break;
            default:
                $this->storage->storeDocumentNotAvailable(
                    $message->getPerson(),
                    $message->getRequestCreatedDate(),
                    $typeId);
                break;
        }
    }

    /**
     * @param int $minDelayTime [sec]
     */
    protected function getDelayStampFromDocumentIndexEntry(DocumentIndexEntry $entry, int $minDelayTime = 0): DelayStamp
    {
        $seconds = $entry->eta->getTimestamp() - time();
        if ($seconds < $minDelayTime) {
            $seconds = $minDelayTime;
        }

        return new DelayStamp($seconds * 1000);
    }

    /**
     * @return AuthenticDocumentType[]
     */
    public function getAuthenticDocumentTypes(string $token): array
    {
        $entries = $this->documentHandler->getDocumentIndex($token);
        $collection = [];
        foreach ($entries as $key => $entry) {
            $id = self::getTypeIdForKey($key);
            if ($id !== null) {
                $name = self::getTypeNameForKey($key);
                assert($name !== null);
                $collection[] = $this->authenticDocumentTypeFromIndexEntry($id, $name, $entry);
            }
        }

        return $collection;
    }

    public function getAuthenticDocumentType(string $id, string $token): AuthenticDocumentType
    {
        $entries = $this->documentHandler->getDocumentIndex($token);
        foreach ($entries as $key => $entry) {
            $entryId = self::getTypeIdForKey($key);
            if ($entryId !== null && $entryId === $id) {
                $name = self::getTypeNameForKey($key);
                assert($name !== null);

                return $this->authenticDocumentTypeFromIndexEntry($id, $name, $entry);
            }
        }
        throw new NotFoundHttpException('AuthenticDocumentType was not found!');
    }

    protected function authenticDocumentTypeFromIndexEntry(string $id, string $name, DocumentIndexEntry $entry): AuthenticDocumentType
    {
        $authenticDocumentType = new AuthenticDocumentType();
        // we must not set the urlsafe_attribute directly as identifier because not all characters are allowed there
        // "." is not allowed by ApiPlatform
        // "/" is not allowed by Symfony
        $authenticDocumentType->setIdentifier($id);
        $authenticDocumentType->setName($name);
        $authenticDocumentType->setAvailabilityStatus($entry->availabilityStatus);
        $authenticDocumentType->setEstimatedTimeOfArrival($entry->eta);

        return $authenticDocumentType;
    }

    protected static function getTypeNameForKey(string $key): ?string
    {
        $mapping = [
            'urn:eidgvat:attributes.user.photo-jpeg-requested' => 'Foto',
            'urn:eidgvat:attributes.user.photo-jpeg-available' => 'Dokument 1',
            'urn:eidgvat:attributes.user.photo-png-available' => 'Dokument 2',
            'urn:eidgvat:attributes.user.photo-jpeg-not-available' => 'Dokument 3',
            'urn:eidgvat:attributes.user.photo' => 'Foto',
        ];

        return $mapping[$key] ?? null;
    }

    protected static function getTypeIdForKey(string $key): ?string
    {
        $mapping = [
            'urn:eidgvat:attributes.user.photo-jpeg-requested' => 'dummy-photo-jpeg-requested',
            'urn:eidgvat:attributes.user.photo-jpeg-available' => 'dummy-photo-jpeg-available',
            'urn:eidgvat:attributes.user.photo-png-available' => 'dummy-photo-png-available',
            'urn:eidgvat:attributes.user.photo-jpeg-not-available' => 'dummy-photo-jpeg-not-available',
            'urn:eidgvat:attributes.user.photo' => 'photo',
        ];

        return $mapping[$key] ?? null;
    }

    protected function getAuthenticDocumentData(string $id, string $token): string
    {
        $entry = $this->getDocumentIndexEntry($id, $token);

        if ($entry === null) {
            throw new NotFoundHttpException('id no found');
        }

        if ($entry->availabilityStatus !== AvailabilityStatus::AVAILABLE) {
            throw new NotFoundHttpException('not available');
        }

        return $this->documentHandler->getDocumentContent($entry, $token);
    }

    public function getAuthenticDocument(string $id, string $token): AuthenticDocument
    {
        $data = $this->getAuthenticDocumentData($id, $token);
        $mimeType = Tools::getMimeType($data);
        $fileExtension = Tools::getFileExtensionForMimeType($mimeType);

        $authenticDocument = new AuthenticDocument();
        $authenticDocument->setIdentifier($id);
        $authenticDocument->setContentUrl(Tools::getDataURI($data, $mimeType));
        $authenticDocument->setName('document.'.$fileExtension);
        $authenticDocument->setContentSize(strlen($data));

        return $authenticDocument;
    }
}
