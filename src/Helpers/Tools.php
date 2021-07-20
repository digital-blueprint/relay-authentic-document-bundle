<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Helpers;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\MimeTypes;

class Tools
{
    /**
     * Convert binary data to a data url.
     */
    public static function getDataURI(string $data, string $mime): string
    {
        return 'data:'.$mime.';base64,'.base64_encode($data);
    }

    public static function getMimeType(string $data): string
    {
        $info = finfo_open();

        return finfo_buffer($info, $data, FILEINFO_MIME_TYPE);
    }

    public static function getFileExtensionForMimeType(string $mimeType): string
    {
        $mimeTypes = new MimeTypes();
        $extensions = $mimeTypes->getExtensions($mimeType);

        return $extensions[0] ?? 'dump';
    }

    public static function createLoggerMiddleware(LoggerInterface $logger): callable
    {
        return Middleware::log(
            $logger,
            new MessageFormatter('[{method}] {uri}: CODE={code}, ERROR={error}, CACHE={res_header_X-Kevinrob-Cache}')
        );
    }

    /**
     * Like json_decode but throws on invalid json data.
     *
     * @throws \JsonException
     *
     * @return mixed
     */
    public static function decodeJSON(string $json, bool $assoc = false)
    {
        return json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
    }
}
