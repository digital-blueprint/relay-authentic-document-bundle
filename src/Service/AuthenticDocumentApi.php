<?php

declare(strict_types=1);
/**
 * Authentic Document API service.
 */

namespace DBP\API\AuthenticDocumentBundle\Service;

use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentRequest;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentType;
use DBP\API\AuthenticDocumentBundle\Message\AuthenticDocumentRequestMessage;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
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
     * @throws ItemNotStoredException
     */
    public function createAuthenticDocumentRequestMessage(AuthenticDocumentRequest $authenticDocumentRequest)
    {
        $token = $authenticDocumentRequest->getToken();
        // TODO: Do we need a setting for this url?
        $url = 'https://eid.egiz.gv.at/documentHandler/documents/document';

        $client = $this->getClient();
        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);
            $data = $this->decodeResponse($response);
            dump($data);
            // TODO: Do we always use photo-jpeg-requested?
//            $documentRequestData = $data["urn:eidgvat:attributes.user.photo-jpeg-requested"];
            $documentRequestData = $data['urn:eidgvat:attributes.user.photo-jpeg-available'];

            $eta = $documentRequestData['eta'];
            $date = new \DateTime($eta === null ? 'now' : $eta);
            $documentToken = $documentRequestData['document_token'];
            $urlAttribute = $documentRequestData['urlsafe_attribute'];

            $seconds = $date->getTimestamp() - time();
            if ($seconds < 0) {
                $seconds = 0;
            }

            // TODO: Remove 1-sec-override to get the real delay
            $seconds = 1;

            // we currently doen't use a person
//            $person = $this->personProvider->getCurrentPerson();
            $person = null;
            $this->bus->dispatch(new AuthenticDocumentRequestMessage($person, $documentToken, $urlAttribute, $date), [
                new DelayStamp($seconds * 1000),
            ]);
        } catch (RequestException $e) {
            $headers = $e->getResponse()->getHeader('WWW-Authenticate');

            if (count($headers) > 0) {
                $message = $headers[0];

                if (preg_match('/error_description="(.+?)"/', $message, $matches)) {
                    $message = $matches[1];
                }
            } else {
                $message = $e->getMessage();
            }

            throw new ItemNotStoredException(sprintf('AuthenticDocumentRequest could not be stored! Message: %s', $message));
        } catch (ItemNotLoadedException $e) {
            throw new ItemNotStoredException(sprintf('AuthenticDocumentRequest could not be stored! Message: %s', $e->getMessage()));
        } catch (GuzzleException $e) {
            throw new ItemNotStoredException(sprintf('AuthenticDocumentRequest could not be stored! Message: %s', $e->getMessage()));
        }
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
        // TODO: Check at egiz server if document is already available
        dump($message);

        $urlAttribute = $message->getUrlAttribute();
        $documentToken = $message->getDocumentToken();

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
            $data = $response->getBody()->getContents();
            // TODO: Store document
            dump('Bytes received: '.strlen($data));
            // TODO: Notify user
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

        // TODO: If document is not available dispatch a new delayed message
        $newMessage = clone $message;
        $newMessage->incRetry();

//        $this->bus->dispatch(new AuthenticDocumentRequestMessage($person, $documentToken, $urlAttribute, $date), [
//            new DelayStamp($seconds * 1000)
//        ]);
    }

    /**
     * @param array $filters
     * @return ArrayCollection
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

        // we must not set the $key directly as identifier because not all characters are allowed there
        $authenticDocumentType->setIdentifier(md5($key));

        $authenticDocumentType->setUrlSafeAttribute($item['urlsafe_attribute']);
        $authenticDocumentType->setAvailabilityStatus($item['availability_status']);
        $authenticDocumentType->setDocumentToken($item['document_token']);
        $authenticDocumentType->setExpireData($item['expires'] !== null ? new \DateTime($item['expires']) : null);
        $authenticDocumentType->setEstimatedTimeOfArrival($item['eta'] !== null ? new \DateTime($item['eta']) : null);

        return $authenticDocumentType;
    }
}
