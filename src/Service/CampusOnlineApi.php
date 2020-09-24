<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Service;

use DBP\API\AuthenticDocumentBundle\Helpers\Tools;
use DBP\API\CoreBundle\Entity\Person;

class CampusOnlineApi implements AuthenticDocumentHandlerProviderInterface
{
    public function persistAuthenticDocument(Person $person, string $documentType, string $documentData) {
        // store document locally for testing
        $path = 'documents';

        // the worker is run in the root path, the webserver is run in /public
        if (!Tools::endsWith(getcwd(), "/public")) {
            $path = "public/" . $path;
        }

        if (!is_dir($path)) {
            mkdir($path);
        }

        $mimeType = Tools::getMimeType($documentData);
        $fileExtension = Tools::getFileExtensionForMimeType($mimeType);
        file_put_contents($path . "/" . $documentType . "." . $fileExtension, $documentData);
    }
}
