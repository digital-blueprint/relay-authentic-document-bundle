<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DocumentHandler;

use DBP\API\CoreBundle\Helpers\GuzzleTools;
use DBP\API\CoreBundle\Helpers\Tools as CoreTools;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use League\Uri\UriTemplate;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class DocumentHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $clientHandler;
    private $idpUrl;
    private $handlerUrl;

    public function __construct()
    {
    }

    public function setIDPUrl(string $idpUrl): void
    {
        $this->idpUrl = $idpUrl;
    }

    public function setHandlerUrl(string $handlerUrl): void
    {
        $this->handlerUrl = $handlerUrl;
    }

    public function setClientHandler(?object $handler): void
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

    private function decodeResponse(ResponseInterface $response): array
    {
        $body = $response->getBody();

        return CoreTools::decodeJSON((string) $body, true);
    }

    public function getUserInfo($token): TokenInformation
    {
        $client = $this->getClient();
        $url = $this->idpUrl.'/profile/oidc/userinfo';
        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ];

        try {
            $response = $client->get($url, $options);
        } catch (RequestException $e) {
            throw new DocumentHandlerException($e->getMessage());
        }

        $data = $this->decodeResponse($response);
        $tokenInformation = new TokenInformation();
        $tokenInformation->birthDate = $data['birthdate'] ?? '';
        $tokenInformation->givenName = $data['given_name'] ?? '';
        $tokenInformation->familyName = $data['family_name'] ?? '';
        $tokenInformation->subject = $data['sub'] ?? '';

        return $tokenInformation;
    }

    /**
     * @return array<string, DocumentIndexEntry>
     */
    public function getDocumentIndex(string $token): array
    {
        $url = $this->handlerUrl.'/documents/document/';
        $client = $this->getClient();
        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ];

        try {
            $response = $client->get($url, $options);
        } catch (RequestException $e) {
            throw new DocumentHandlerException($e->getMessage());
        }

        $json = $this->decodeResponse($response);
        $results = [];
        foreach ($json as $key => $item) {
            $entry = new DocumentIndexEntry();
            $entry->availabilityStatus = $item['availability_status'];
            $entry->eta = new \DateTimeImmutable($item['eta']);
            $entry->documentToken = $item['document_token'];
            $entry->urlsafeAttribute = $item['urlsafe_attribute'];
            $entry->expires = new \DateTimeImmutable($item['expires']);
            $entry->errorMessage = $item['error_message'] ?? '';
            $results[$key] = $entry;
        }

        return $results;
    }

    public function getDocumentContent(DocumentIndexEntry $entry, string $token): string
    {
        $uriTemplate = new UriTemplate('/documents/document/{attribute}');
        $url = $this->handlerUrl.(string) $uriTemplate->expand([
            'attribute' => $entry->urlsafeAttribute,
        ]);

        $client = $this->getClient();
        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ];

        try {
            $response = $client->get($url, $options);
        } catch (RequestException $e) {
            throw new DocumentHandlerException($e->getMessage());
        }

        return (string) $response->getBody();
    }
}
