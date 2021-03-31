<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Tests\DocumentHandler;

use DBP\API\AuthenticDocumentBundle\DocumentHandler\AvailabilityStatus;
use DBP\API\AuthenticDocumentBundle\DocumentHandler\DocumentHandler;
use DBP\API\AuthenticDocumentBundle\DocumentHandler\DocumentHandlerException;
use DBP\API\AuthenticDocumentBundle\DocumentHandler\DocumentIndexEntry;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DocumentHandlerTest extends WebTestCase
{
    /**
     * @var DocumentHandler
     */
    private $api;

    protected function setUp(): void
    {
        $nullLogger = new Logger('dummy', [new NullHandler()]);
        $this->api = new DocumentHandler();
        $this->api->setLogger($nullLogger);
        $this->api->setHandlerUrl('http://nope');
        $this->api->setIDPUrl('http://nope');
        $this->mockResponses([]);
    }

    private function mockResponses(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->api->setClientHandler($stack);
    }

    public function testUserInfo()
    {
        $UINFO_RESPONSE = file_get_contents(__DIR__.'/userinfo-response.json');
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json'], $UINFO_RESPONSE),
        ]);
        $tokenInfo = $this->api->getUserInfo('dummy');
        $this->assertSame('Family', $tokenInfo->familyName);
        $this->assertSame('Given', $tokenInfo->givenName);
        $this->assertSame('BF:foobar', $tokenInfo->subject);
        $this->assertSame('1970-01-01', $tokenInfo->birthDate);
    }

    public function testUserInfoInvalidToken()
    {
        $this->mockResponses([
            new Response(400, ['Content-Type' => 'application/json'],
                '{"error_description":"Invalid grant","error":"invalid_grant"}'),
        ]);
        $this->expectException(DocumentHandlerException::class);
        $this->api->getUserInfo('dummy');
    }

    public function testGetDocumentIndexRequested()
    {
        $INDEX_RESPONSE = file_get_contents(__DIR__.'/document-index-requested.json');
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json'], $INDEX_RESPONSE),
        ]);
        $index = $this->api->getDocumentIndex('foobar');
        $this->assertCount(1, $index);
        $key = 'urn:eidgvat:attributes.user.photo';
        $this->assertArrayHasKey($key, $index);
        $entry = $index[$key];
        $this->assertSame(AvailabilityStatus::REQUESTED, $entry->availabilityStatus);
    }

    public function testGetDocumentIndexNotAvailable()
    {
        // This was a real error response when both backends at egiz were offline
        $INDEX_RESPONSE = file_get_contents(__DIR__.'/document-index-not-available.json');
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json'], $INDEX_RESPONSE),
        ]);
        $index = $this->api->getDocumentIndex('foobar');
        $entry = $index['urn:eidgvat:attributes.user.photo'];
        $this->assertSame(AvailabilityStatus::NOT_AVAILABLE, $entry->availabilityStatus);
        $this->assertStringContainsString('Could not fetch the document', $entry->errorMessage);
    }

    public function testGetDocumentIndexAvailable()
    {
        $INDEX_RESPONSE = file_get_contents(__DIR__.'/document-index-available.json');
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json'], $INDEX_RESPONSE),
        ]);
        $index = $this->api->getDocumentIndex('foobar');
        $entry = $index['urn:eidgvat:attributes.user.photo'];
        $this->assertSame(AvailabilityStatus::AVAILABLE, $entry->availabilityStatus);
    }

    public function testGetDocumentContentInvalidToken()
    {
        $this->mockResponses([
            new Response(401, ['Content-Type' => 'application/json'], ''),
        ]);
        $entry = new DocumentIndexEntry();
        $entry->urlsafeAttribute = 'photo';
        $this->expectException(DocumentHandlerException::class);
        $this->api->getDocumentContent($entry, 'foobar');
    }

    public function testGetDocumentContent()
    {
        $entry = new DocumentIndexEntry();
        $entry->urlsafeAttribute = 'photo';

        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=', true);
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'image/jpeg'], $jpeg),
        ]);

        $data = $this->api->getDocumentContent($entry, 'foobar');
        $this->assertSame($jpeg, $data);

        $finfo = new \finfo(FILEINFO_MIME);
        $mime = $finfo->buffer($data);
        $this->assertStringContainsString('image/jpeg', $mime);
    }

    public function testGetDocumentContentNotAvailable()
    {
        $entry = new DocumentIndexEntry();
        $entry->urlsafeAttribute = 'photo';
        $this->mockResponses([
            // Taken from a real response
            new Response(404, ['Content-Type' => 'image/_*'],
                'Document not found. Either attribute does not exist or document is not available. Consult'),
        ]);

        $this->expectException(DocumentHandlerException::class);
        $this->api->getDocumentContent($entry, 'foobar');
    }
}
