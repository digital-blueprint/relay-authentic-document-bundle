<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Tests;

use DBP\API\AuthenticDocumentBundle\UCard\UCard;
use DBP\API\AuthenticDocumentBundle\UCard\UCardException;
use DBP\API\AuthenticDocumentBundle\UCard\UCardPicture;
use DBP\API\AuthenticDocumentBundle\UCard\UCardService;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UCardTest extends WebTestCase
{
    public const IDENT = '054792FDE3956438';

    private $api;

    protected function setUp(): void
    {
        $nullLogger = new Logger('dummy', [new NullHandler()]);
        $this->api = new UCardService($nullLogger);
        $this->api->setToken('sometoken');
        $this->mockResponses([]);
    }

    private function mockResponses(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->api->setClientHandler($stack);
    }

    public function testGetForIdentNoPermissions()
    {
        // from real response with wrong token
        $NO_AUTH_RESPONSE = '<?xml version = \'1.0\' encoding = \'UTF-8\'?> <codata:resources xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:codata="http://www.campusonline.at/xsd/meta/codata/v1" xsi:schemaLocation="http://www.campusonline.at/xsd/meta/codata/v1 codata.xsd"> <resource> <content type="model-core.lib.codata.error.coErrorDto"> <coErrorDto> <reference>592280910</reference> <message>Warnung</message> <requestUrl></requestUrl> <httpCode>403</httpCode> <errorType>Warnung</errorType> <imageUrl>https://online.tugraz.at/prod/img/msg_warning.gif</imageUrl> </coErrorDto> </content> </resource> </codata:resources>';
        $this->mockResponses([
            new Response(403, ['Content-Type' => 'application/xml'], $NO_AUTH_RESPONSE),
        ]);
        $this->expectException(UCardException::class);
        $this->api->getCardForIdent(self::IDENT);
    }

    public function testGetForIdent()
    {
        // copied from the spec
        $UCARD_GET_RESPONSE = '<?xml version = \'1.0\' encoding = \'UTF-8\'?> <codata:resources xmlns:codata="http://www.campusonline.at/xsd/meta/codata/v1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.campusonline.at/xsd/meta/codata/v1 codata.xsd"> <!-- list resource links --> <link rel="create" summary="create new entry or updte existing entry. HTTP-method POST" href="https://&lt;instanz/dad&gt;/pl/rest/brm.pm.extension.ucardfoto?$ctx=lang=de" type="model-brm.brm.pm.extension.ucardfoto.plsqlCardPictureDto"/> <!-- <resource>...</resource> there may be zero to N resource elements --> <resource> <!-- resouce links --> <link rel="contents" summary="GET bindary content of picture." href="https://&lt;instanz/dad&gt;/pl/rest/brm.pm.extension.ucardfoto/${contentId} /content?$ctx=lang=de" type="model-brm.brm.pm.extension.ucardfoto.plsqlCardPicture"/> <link rel="create" summary="POST (create or update) bindary content of picture. POST as multipart/form-data" href="https://&lt;instanz/dad&gt;/pl/rest/brm.pm.extension.ucardfoto/${contentId} /content?$ctx=lang=de" type="model-brm.brm.pm.extension.ucardfoto.plsqlCardPicture"/> <content type="model-brm.brm.pm.extension.ucardfoto.plsqlCardPictureDto"> <plsqlCardPictureDto> <CARD_TYPE>STA</CARD_TYPE> <IDENT_NR_OBFUSCATED>054792FDE3956438</IDENT_NR_OBFUSCATED> <CONTENT_ID>1</CONTENT_ID> <IS_UPDATABLE>true</IS_UPDATABLE> <CONTENT_SIZE>0</CONTENT_SIZE> </plsqlCardPictureDto> </content> </resource> </codata:resources>';
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/xml'], $UCARD_GET_RESPONSE),
        ]);
        $card = $this->api->getCardForIdent(self::IDENT);
        $this->assertInstanceOf(UCard::class, $card);
        $this->assertSame('054792FDE3956438', $card->ident);
        $this->assertSame('STA', $card->cardType);
        $this->assertSame('1', $card->contentId);
        $this->assertSame(0, $card->contentSize);
        $this->assertSame(true, $card->isUpdatable);
    }

    public function testGetCardPhoto()
    {
        $PICTURE_RESPONSE = '<?xml version = \'1.0\' encoding = \'UTF-8\'?> <codata:resources xmlns:codata="http://www.campusonline.at/xsd/meta/codata/v1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.campusonline.at/xsd/meta/codata/v1 codata.xsd"> <resource> <content type="model-brm.brm.pm.extension.ucardfoto.plsqlCardPicture"> <plsqlCardPicture> <ID>28748</ID> <CONTENT>/9j/4AAQSkZJRgABAgAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkS</CONTENT> </plsqlCardPicture> </content> </resource> </codata:resources>';

        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/xml'], $PICTURE_RESPONSE),
        ]);

        $card = new UCard('', '', '', 0, false);
        $pic = $this->api->getCardPicture($card);
        $this->assertInstanceOf(UCardPicture::class, $pic);
        $this->assertSame('28748', $pic->id);
        $this->assertStringContainsString('JFIF', $pic->content);
    }
}
