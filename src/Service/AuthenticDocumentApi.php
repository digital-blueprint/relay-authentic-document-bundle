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
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Helpers\JsonException;
use DBP\API\CoreBundle\Helpers\Tools as CoreTools;
use DBP\API\CoreBundle\Service\GuzzleLogger;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    private $clientHandler;
    private $guzzleLogger;

    public function __construct(MessageBusInterface $bus, PersonProviderInterface $personProvider, GuzzleLogger $guzzleLogger)
    {
        $this->bus = $bus;
        $this->personProvider = $personProvider;
        $this->clientHandler = null;
        $this->guzzleLogger = $guzzleLogger;
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

        $stack->push($this->guzzleLogger->getClientHandler());

        return new Client($client_options);
    }

    /**
     * Creates Symfony message and dispatch delayed message to download a document from egiz in the future.
     *
     * @param AuthenticDocumentRequest $authenticDocumentRequest
     * @return AuthenticDocumentRequestMessage
     */
    public function createAndDispatchAuthenticDocumentRequestMessage(AuthenticDocumentRequest $authenticDocumentRequest)
    {
        $token = $authenticDocumentRequest->getToken();
        $typeId = $authenticDocumentRequest->getTypeId();
        $authenticDocumentType = $this->getAuthenticDocumentType($typeId, ["token" => $token]);
        $availabilityStatus = $authenticDocumentType->getAvailabilityStatus();

        if ($availabilityStatus == "not_available") {
            throw new NotFoundHttpException("Document is not available");
        }

        $estimatedTimeOfArrival = $authenticDocumentType->getEstimatedTimeOfArrival();
        $documentToken = $authenticDocumentType->getDocumentToken();
        $seconds = $estimatedTimeOfArrival->getTimestamp() - time();

        if ($seconds < 0) {
            $seconds = 0;
        }

        // TODO: we currently don't use a person
//        $person = $this->personProvider->getCurrentPerson();
        $person = null;
        $message = new AuthenticDocumentRequestMessage($person, $documentToken, $typeId, $estimatedTimeOfArrival);

        $this->bus->dispatch(
            $message, [
            new DelayStamp($seconds * 1000),
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
     * @throws ItemNotLoadedException
     */
    public function handleRequestMessage(AuthenticDocumentRequestMessage $message)
    {
        $documentToken = $message->getDocumentToken();
        $typeId = $message->getTypeId();
        $filters = ["token" => $documentToken];
        // check if document is already available
        $authenticDocumentType = $this->getAuthenticDocumentType($typeId, $filters);

        switch ($authenticDocumentType->getAvailabilityStatus()) {
            case "available":
                try {
                    $data = $this->getAuthenticDocumentData($typeId, $filters);

                    // store document locally for testing
                    $path = 'documents';

                    // the worker is run in the root path, the webserver is run in /public
                    if (!Tools::endsWith(getcwd(), "/public")) {
                        $path = "public/" . $path;
                    }

                    if (!is_dir($path)) {
                        mkdir($path);
                    }

                    $mimeType = Tools::getMimeType($data);
                    $fileExtension = Tools::getFileExtensionForMimeType($mimeType);
                    file_put_contents($path . "/" . $typeId . "." . $fileExtension, $data);
                } catch (ItemNotLoadedException $e) {
                }

                break;
            case "requested":
                // TODO: If document is not available dispatch a new delayed message
                $newMessage = clone $message;
                $newMessage->incRetry();

//        $this->bus->dispatch(new AuthenticDocumentRequestMessage($person, $documentToken, $urlAttribute, $date), [
//            new DelayStamp($seconds * 1000)
//        ]);
                break;
            default:
//                throw new NotFoundHttpException("AuthenticDocument was not found!");
                break;
        }
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
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $message = $body->getContents();

            throw new ItemNotLoadedException(sprintf('Document Types could not be loaded! Message: %s', $message));
        } catch (GuzzleException $e) {
            throw new ItemNotLoadedException(sprintf('Document Types could not be loaded! Message: %s', $e->getMessage()));
        } catch (ItemNotLoadedException $e) {
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

    public function getAuthenticDocumentData($id, $filters): string {
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
        $data = $this->getAuthenticDocumentData($id, $filters);
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
