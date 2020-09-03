<?php

declare(strict_types=1);
/**
 * Egiz Image API service.
 */

namespace DBP\API\AuthenticDocumentBundle\Service;


use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Helpers\JsonException;
use DBP\API\CoreBundle\Helpers\Tools as CoreTools;
use DBP\API\CoreBundle\Service\GuzzleLogger;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use DBP\API\AuthenticDocumentBundle\Entity\AuthenticDocumentRequest;
use DBP\API\AuthenticDocumentBundle\Message\AuthenticDocumentRequestMessage;
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
     *
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

    public function createAuthenticDocumentRequestMessage(AuthenticDocumentRequest $authenticImageRequest)
    {
        $token = $authenticImageRequest->getToken();
        // TODO: Do we need a setting for this url?
        $url = "https://eid.egiz.gv.at/documentHandler/documents/document";

        $client = $this->getClient();
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);
            $data = $this->decodeResponse($response);
            dump($data);
            // TODO: Do we always use photo-jpeg-requested?
//            $documentRequestData = $data["urn:eidgvat:attributes.user.photo-jpeg-requested"];
            $documentRequestData = $data["urn:eidgvat:attributes.user.photo-jpeg-available"];

            $eta = $documentRequestData["eta"];
            $date = new \DateTime($eta === null ? "now" : $eta);
            $documentToken = $documentRequestData["document_token"];
            $urlAttribute = $documentRequestData["urlsafe_attribute"];

            $seconds = $date->getTimestamp() - time();
            if ($seconds < 0) {
                $seconds = 0;
            }

            // TODO: Remove 1-sec-override to get the real delay
            $seconds = 1;

            $person = $this->personProvider->getCurrentPerson();
            $envelope = $this->bus->dispatch(new AuthenticDocumentRequestMessage($person, $documentToken, $urlAttribute, $date), [
                new DelayStamp($seconds * 1000)
            ]);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $message = $body->getContents();

            throw new ItemNotStoredException(sprintf("AuthenticDocumentRequest could not be stored! Message: %s", $message));
        } catch (ItemNotLoadedException $e) {
            throw new ItemNotStoredException(sprintf("AuthenticDocumentRequest could not be stored! Message: %s", $e->getMessage()));
        } catch (GuzzleException $e) {
            throw new ItemNotStoredException(sprintf("AuthenticDocumentRequest could not be stored! Message: %s", $e->getMessage()));
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

    public function handleRequestMessage(AuthenticDocumentRequestMessage $message)
    {
        // TODO: Check at egiz server if image is already available
        dump($message);

        $urlAttribute = $message->getUrlAttribute();
        $documentToken = $message->getDocumentToken();

        // TODO: Do we need a setting for this url?
        $url = "https://eid.egiz.gv.at/documentHandler/documents/document/$urlAttribute";

        $client = $this->getClient();
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $documentToken,
            ],
        ];

        try {
            // http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=get#making-a-request
            $response = $client->request('GET', $url, $options);
            $data = $response->getBody()->getContents();
            // TODO: Store document
            dump("Bytes received: " . strlen($data));
            // TODO: Notify user
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $message = $body->getContents();

            throw new ItemNotLoadedException(sprintf("Document could not be loaded! Message: %s", $message));
        } catch (ItemNotLoadedException $e) {
            throw new ItemNotLoadedException(sprintf("Document could not be loaded! Message: %s", $e->getMessage()));
        } catch (GuzzleException $e) {
            throw new ItemNotLoadedException(sprintf("Document could not be loaded! Message: %s", $e->getMessage()));
        }

        // TODO: If image is not available dispatch a new delayed message
    }
}
