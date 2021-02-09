<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\UCard;

use DBP\API\CoreBundle\Helpers\GuzzleTools;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use League\Uri\UriTemplate;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class UCardService
{
    /**
     * @var ?string
     */
    private $token;
    private $baseUrl;
    private $clientHandler;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->token = null;
        $this->logger = $logger;
        $this->baseUrl = 'https://online.tugraz.at/tug_online';
    }

    public function setToken(string $token)
    {
        $this->token = $token;
    }

    public function setClientHandler(?object $handler)
    {
        $this->clientHandler = $handler;
    }

    private function getClient(): Client
    {
        if ($this->token === null) {
            throw new \UnexpectedValueException('token is not set');
        }
        $stack = HandlerStack::create($this->clientHandler);
        $base_uri = $this->baseUrl;
        if (substr($base_uri, -1) !== '/') {
            $base_uri .= '/';
        }

        $client_options = [
            'base_uri' => $base_uri,
            'handler' => $stack,
        ];

        $stack->push(GuzzleTools::createLoggerMiddleware($this->logger));

        $client = new Client($client_options);

        return $client;
    }

    public function getCardForIdent(string $ident): UCard
    {
        $filter = 'IDENT_NR_OBFUSCATED-eq='.$ident;
        $uriTemplate = new UriTemplate('pl/rest/brm.pm.extension.ucardfoto{?access_token,%24filter}');
        $uri = (string) $uriTemplate->expand([
            'access_token' => $this->token,
            '%24filter' => $filter,
        ]);

        $client = $this->getClient();
        try {
            $response = $client->get($uri);
        } catch (RequestException $e) {
            throw new UCardException($e->getMessage());
        }

        return $this->parseGetResponse($response);
    }

    public function parseGetResponse(ResponseInterface $response): UCard
    {
        $content = (string) $response->getBody();

        $xml = new \SimpleXMLElement($content);

        $pic = $xml->xpath('/codata:resources/resource/content/plsqlCardPictureDto');
        if ($pic === false || count($pic) !== 1) {
            throw new UCardException('missing content');
        }
        $picArray = (array) $pic[0];

        // TODO: more error handling
        $cardType = $picArray['CARD_TYPE'];
        $ident = $picArray['IDENT_NR_OBFUSCATED'];
        $contentId = $picArray['CONTENT_ID'];
        $isUpdatable = $picArray['IS_UPDATABLE'] === 'true';
        $contentSize = intval($picArray['CONTENT_SIZE']);

        return new UCard($ident, $cardType, $contentId, $contentSize, $isUpdatable);
    }

    public function getCardPicture(UCard $card): UCardPicture
    {
        $uriTemplate = new UriTemplate('pl/rest/brm.pm.extension.ucardfoto/{contentId}/content{?access_token}');
        $uri = (string) $uriTemplate->expand([
            'contentId' => $card->contentId,
            'access_token' => $this->token,
        ]);

        $client = $this->getClient();
        try {
            $response = $client->get($uri);
        } catch (RequestException $e) {
            throw new UCardException($e->getMessage());
        }

        return $this->parseGetContentResponse($response);
    }

    public function parseGetContentResponse(ResponseInterface $response): UCardPicture
    {
        $content = (string) $response->getBody();
        $xml = new \SimpleXMLElement($content);

        $pic = $xml->xpath('/codata:resources/resource/content/plsqlCardPicture');
        if ($pic === false || count($pic) !== 1) {
            throw new UCardException('missing content');
        }
        $picArray = (array) $pic[0];

        // TODO: more error handling
        $id = $picArray['ID'];
        $b64content = $picArray['CONTENT'];
        $content = base64_decode($b64content, true);
        if ($content === false) {
            throw new UCardException('Invalid content');
        }

        return new UCardPicture($id, $content);
    }

    public function createCardForIdent(string $ident, string $cardType): UCard
    {
        // POST https://<instanz/dad>/pl/rest/brm.pm.extension.ucardfoto?access_token=TOKEN$filter=IDENT_NR_OBFUSCATED-eq=054792FDE3956438
        // CARD_TYPE = STA
        // IDENT_NR_OBFUSCATED = 054792FDE3956438

        return new UCard('', '', '', 0, false);
    }

    public function setCardPhoto(UCard $card, string $data)
    {
        // POST https://<instanz/dad>/pl/rest/brm.pm.extension.ucardfoto/${contentId}/content?access_token=TOKEN
        // Content = base64
    }
}
