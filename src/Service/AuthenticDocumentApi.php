<?php

declare(strict_types=1);
/**
 * Authentic Document API service.
 */

namespace DBP\API\AuthenticDocumentBundle\Service;

use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocument;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentRequest;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentType;
use DBP\API\AuthenticDocumentBundle\Helpers\Tools;
use DBP\API\AuthenticDocumentBundle\Message\AuthenticDocumentRequestMessage;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Helpers\GuzzleTools;
use DBP\API\CoreBundle\Helpers\JsonException;
use DBP\API\CoreBundle\Helpers\Tools as CoreTools;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class AuthenticDocumentApi
{
    /**
     * @var MessageBusInterface
     */
    private $bus;

    private $clientHandler;

    private $logger;

    /**
     * @var PersonProviderInterface
     */
    private $personProvider;

    /**
     * @var AuthenticDocumentHandlerProviderInterface
     */
    private $authenticDocumentHandlerProvider;


    public function __construct(
        MessageBusInterface $bus,
        LoggerInterface $logger,
        PersonProviderInterface $personProvider,
        AuthenticDocumentHandlerProviderInterface $authenticDocumentHandlerProvider
    )
    {
        $this->bus = $bus;
        $this->clientHandler = null;
        $this->logger = $logger;
        $this->personProvider = $personProvider;
        $this->authenticDocumentHandlerProvider = $authenticDocumentHandlerProvider;
    }

    /**
     * Replace the guzzle client handler for testing.
     * @param object|null $handler
     */
    public function setClientHandler(?object $handler)
    {
        $this->clientHandler = $handler;
    }

    private function getClient(): Client
    {
        $stack = HandlerStack::create($this->clientHandler);

        $client_options = [
            'handler' => $stack,
        ];

        $stack->push(GuzzleTools::createLoggerMiddleware($this->logger));

        return new Client($client_options);
    }

    /**
     * Creates Symfony message and dispatch delayed message to download a document from egiz in the future.
     *
     * @param AuthenticDocumentRequest $authenticDocumentRequest
     * @param string $authorizationHeader
     * @return AuthenticDocumentRequestMessage
     * @throws GuzzleException
     * @throws ItemNotLoadedException
     */
    public function createAndDispatchAuthenticDocumentRequestMessage(
        AuthenticDocumentRequest $authenticDocumentRequest, string $authorizationHeader)
    {
        $token = $authenticDocumentRequest->getToken();
        $typeId = $authenticDocumentRequest->getTypeId();
        $authenticDocumentType = $this->getAuthenticDocumentType($typeId, ["token" => $token]);
        $availabilityStatus = $authenticDocumentType->getAvailabilityStatus();

        if ($availabilityStatus == "not_available") {
            throw new NotFoundHttpException("Document is not available");
        }

        // we can decode the token here after if was proven valid by the request in getAuthenticDocumentType
        // note: it would also be possible to get the information from Keycloak directly but we don't want
        //       to be locked in into it and don't know if all data is available
        //       (https://auth-dev.tugraz.at/auth/realms/tugraz/broker/eid-oidc/token)
        $tokenInformation = $this->fetchTokenInformation($token);
        $givenName = $tokenInformation->givenName;
        $familyName = $tokenInformation->familyName;
        $birthDay = $tokenInformation->birthDate;

        // try to match name and birthday to a person
        $people = $this->personProvider->getPersonsByNameAndBirthday($givenName, $familyName, $birthDay);
        $peopleCount = count($people);

        if ($peopleCount == 0) {
            throw new NotFoundHttpException("Person $givenName $familyName could not be found!");
        } else if ($peopleCount > 1) {
            throw new NotFoundHttpException("Multiple people with name $givenName $familyName were found!");
        }

        $estimatedTimeOfArrival = $authenticDocumentType->getEstimatedTimeOfArrival();
        $dateCreated = $authenticDocumentRequest->getDateCreated();
        $documentToken = $authenticDocumentType->getDocumentToken();

        $message = new AuthenticDocumentRequestMessage($people[0], $documentToken, $typeId, $dateCreated, $estimatedTimeOfArrival);

        $this->bus->dispatch(
            $message, [
            $this->getDelayStampFromAuthenticDocumentType($authenticDocumentType),
        ]);

        return $message;
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     *
     * @throws ItemNotLoadedException
     */
    private function decodeResponse(ResponseInterface $response)
    {
        $body = $response->getBody();
        try {
            return CoreTools::decodeJSON((string) $body, true);
        } catch (JsonException $e) {
            throw new ItemNotLoadedException(sprintf('Invalid json: %s', CoreTools::filterErrorMessage($e->getMessage())));
        }
    }

    /**
     * Handle Symfony Message AuthenticDocumentRequestMessage to download the document from the egiz server.
     *
     * @param AuthenticDocumentRequestMessage $message
     */
    public function handleRequestMessage(AuthenticDocumentRequestMessage $message)
    {
        dump($message);
        $documentToken = $message->getDocumentToken();
        $typeId = $message->getTypeId();
        $filters = ["token" => $documentToken];
        // check if document is already available
        $authenticDocumentType = $this->getAuthenticDocumentType($typeId, $filters);

        switch ($authenticDocumentType->getAvailabilityStatus()) {
            case "available":
                try {
                    $data = $this->fetchAuthenticDocumentData($typeId, $filters);

                    $this->authenticDocumentHandlerProvider->persistAuthenticDocument(
                        $message->getPerson(),
                        $message->getRequestCreatedDate(),
                        $typeId,
                        $data);
                } catch (\Exception $e) {
                    $this->authenticDocumentHandlerProvider->handleAuthenticDocumentFetchException(
                        $message->getPerson(),
                        $message->getRequestCreatedDate(),
                        $typeId,
                        $e->getMessage());
                }

                break;
            case "requested":
                // if document is not yet available dispatch a new delayed message
                $newMessage = clone $message;
                $newMessage->incRetry();
                $newMessage->setEstimatedResponseDate($authenticDocumentType->getEstimatedTimeOfArrival());

                // wait at least 30 sec
                $this->bus->dispatch($newMessage, [
                    $this->getDelayStampFromAuthenticDocumentType($authenticDocumentType, 30)
                ]);
                break;
            default:
                $this->authenticDocumentHandlerProvider->handleAuthenticDocumentNotAvailable(
                    $message->getPerson(),
                    $message->getRequestCreatedDate(),
                    $typeId);
                break;
        }
    }

    /**
     * @param $token
     * @return TokenInformation
     * @throws GuzzleException
     * @throws ItemNotLoadedException
     */
    public function fetchTokenInformation($token): TokenInformation {
        // TODO: Do we need a setting for this url?
        $url = "https://eid.egiz.gv.at/idp/profile/oidc/userinfo";

        $client = $this->getClient();

        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);

            //  Returns:
            //  [
            //    ...
            //    "birthdate" => "1994-12-31"
            //    "given_name" => "XXXClaus - Maria"
            //    "family_name" => "XXXvon Brandenburg"
            // ]
            $data = $this->decodeResponse($response);

            $tokenInformation = new TokenInformation();
            $tokenInformation->birthDate = new \DateTime($data["birthdate"]);
            $tokenInformation->givenName = $data["given_name"];
            $tokenInformation->familyName = $data["family_name"];

            return $tokenInformation;
        } catch (\Exception $e) {
            throw new ItemNotLoadedException(sprintf('Token information could not be loaded! Message: %s', $e->getMessage()));
        }
    }

    /**
     * @param $authenticDocumentType
     * @param int $minDelayTime [sec]
     * @return DelayStamp
     */
    public function getDelayStampFromAuthenticDocumentType($authenticDocumentType, $minDelayTime = 0): DelayStamp {
        $estimatedTimeOfArrival = $authenticDocumentType->getEstimatedTimeOfArrival();
        $seconds = $estimatedTimeOfArrival->getTimestamp() - time();

        if ($seconds < $minDelayTime) {
            $seconds = $minDelayTime;
        }

        return new DelayStamp($seconds * 1000);
    }

    /**
     * @param array $filters
     * @return ArrayCollection|AuthenticDocumentType[]
     * @throws ItemNotLoadedException
     */
    public function getAuthenticDocumentTypes(array $filters): ArrayCollection
    {
        /** @var ArrayCollection<int,AuthenticDocumentType> $collection */
        $collection = new ArrayCollection();

        $authenticDocumentTypesJsonData = $this->getAuthenticDocumentTypesJsonData($filters);

        foreach ($authenticDocumentTypesJsonData as $key => $jsonData) {
            $collection->add($this->authenticDocumentTypeFromJsonItem($key, $jsonData));
        }

        return $collection;
    }

    /**
     * @param $id
     * @param array $filters
     * @return AuthenticDocumentType
     */
    public function getAuthenticDocumentType($id, array $filters): AuthenticDocumentType
    {
        try {
            $authenticDocumentTypes = $this->getAuthenticDocumentTypes($filters);
        } catch (ItemNotLoadedException $e) {
            throw new NotFoundHttpException("AuthenticDocumentType was not found!");
        }

        foreach($authenticDocumentTypes as $authenticDocumentType) {
            if ($authenticDocumentType->getIdentifier() == $id) {
                return $authenticDocumentType;
            }
        }

        throw new NotFoundHttpException("AuthenticDocumentType was not found!");
    }

    public function getAuthenticDocumentTypesJsonData($filters): array {
        $token = $filters['token'] ?? '';

        if ($token === '') {
            return [];
        }

        // TODO: Do we need a setting for this url?
        $url = "https://eid.egiz.gv.at/documentHandler/documents/document/";

        $client = $this->getClient();

        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);

            return $this->decodeResponse($response);
        } catch (\Exception $e) {
            throw new ItemNotLoadedException(sprintf('Document Types could not be loaded! Message: %s', $e->getMessage()));
        }
    }

    public function authenticDocumentTypeFromJsonItem(string $key, array $item) :AuthenticDocumentType {
        $authenticDocumentType = new AuthenticDocumentType();
        $availabilityStatus = $item['availability_status'];
        $estimatedTimeOfArrival = $item['eta'] !== null ? new \DateTime($item['eta']) : null;
        if ($availabilityStatus == "available") {
            $estimatedTimeOfArrival = new \DateTime();
        }

        // we must not set the urlsafe_attribute directly as identifier because not all characters are allowed there
        // "." is not allowed by ApiPlatform
        // "/" is not allowed by Symfony
//        $authenticDocumentType->setIdentifier(urlencode(base64_encode($item['urlsafe_attribute'])));
        $authenticDocumentType->setIdentifier(self::getAuthenticDocumentTypeKeyIdentifierMapping($key));
        $authenticDocumentType->setName(self::getAuthenticDocumentTypeKeyNameMapping($key));
        $authenticDocumentType->setUrlSafeAttribute($item['urlsafe_attribute']);
        $authenticDocumentType->setAvailabilityStatus($availabilityStatus);
        $authenticDocumentType->setDocumentToken($item['document_token']);
        $authenticDocumentType->setExpiryDate($item['expires'] !== null ? new \DateTime($item['expires']) : null);
        $authenticDocumentType->setEstimatedTimeOfArrival($estimatedTimeOfArrival);

        return $authenticDocumentType;
    }

    /**
     * @param null|string $key
     * @return array|string
     */
    public static function getAuthenticDocumentTypeKeyNameMapping($key = null) {
        $mapping = [
            'urn:eidgvat:attributes.user.photo-jpeg-requested' => 'Foto',
            'urn:eidgvat:attributes.user.photo-jpeg-available' => 'Dokument 1',
            'urn:eidgvat:attributes.user.photo-png-available' => 'Dokument 2',
            'urn:eidgvat:attributes.user.photo-jpeg-not-available' => 'Dokument 3',
        ];

        return ($key === null) ? $mapping : ($mapping[$key] ?? "");
    }

    /**
     * @param null|string $key
     * @return array|string
     */
    public static function getAuthenticDocumentTypeKeyIdentifierMapping($key = null) {
        $mapping = [
            'urn:eidgvat:attributes.user.photo-jpeg-requested' => 'dummy-photo-jpeg-requested',
            'urn:eidgvat:attributes.user.photo-jpeg-available' => 'dummy-photo-jpeg-available',
            'urn:eidgvat:attributes.user.photo-png-available' => 'dummy-photo-png-available',
            'urn:eidgvat:attributes.user.photo-jpeg-not-available' => 'dummy-photo-jpeg-not-available',
        ];

        return ($key === null) ? $mapping : ($mapping[$key] ?? "");
    }

    /**
     * @param null|string $id
     * @return array|string
     */
    public static function getAuthenticDocumentTypeIdentifierKeyMapping($id = null) {
        $mapping = array_flip(self::getAuthenticDocumentTypeKeyIdentifierMapping());

        return ($id === null) ? $mapping : ($mapping[$id] ?? "");
    }

    public function fetchAuthenticDocumentData($id, $filters): string {
        if ($id == "") {
            throw new NotFoundHttpException("No id was set");
        }

        $authenticDocumentType = $this->getAuthenticDocumentType($id, $filters);

        switch ($authenticDocumentType->getAvailabilityStatus()) {
            case "available":
                $urlAttribute = $authenticDocumentType->getUrlSafeAttribute();
                $documentToken = $authenticDocumentType->getDocumentToken();

                // TODO: Do we need a setting for this url?
                $url = "https://eid.egiz.gv.at/documentHandler/documents/document/$urlAttribute";

                $client = $this->getClient();
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer '.$documentToken,
                    ],
                ];

                try {
                    // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
                    $response = $client->request('GET', $url, $options);

                    return $response->getBody()->getContents();
                } catch (RequestException $e) {
                    $response = $e->getResponse();
                    $body = $response->getBody();
                    $message = $body->getContents();

                    throw new ItemNotLoadedException(sprintf('Document could not be loaded! Message: %s', $message));
                } catch (ItemNotLoadedException $e) {
                    throw new ItemNotLoadedException(sprintf('Document could not be loaded! Message: %s', $e->getMessage()));
                } catch (GuzzleException $e) {
                    throw new ItemNotLoadedException(sprintf('Document could not be loaded! Message: %s', $e->getMessage()));
                }
            case "requested":
                throw new NotFoundHttpException("AuthenticDocument is not yet available!");
            default:
                throw new NotFoundHttpException("AuthenticDocument was not found!");
        }
    }

    /**
     * @param $id
     * @param $filters
     * @return AuthenticDocument
     * @throws ItemNotLoadedException
     */
    public function getAuthenticDocument($id, $filters): AuthenticDocument {
        $data = $this->fetchAuthenticDocumentData($id, $filters);
        $mimeType = Tools::getMimeType($data);
        $fileExtension = Tools::getFileExtensionForMimeType($mimeType);

        $authenticDocument = new AuthenticDocument();
        $authenticDocument->setIdentifier($id);
        $authenticDocument->setContentUrl(Tools::getDataURI($data, $mimeType));
        $authenticDocument->setName("document." . $fileExtension);
        $authenticDocument->setContentSize(strlen($data));

        return $authenticDocument;
    }
}

class TokenInformation {
    /**
     * @var \DateTime
     */
    public $birthDate;

    /**
     * @var string
     */
    public $givenName;

    /**
     * @var string
     */
    public $familyName;
}