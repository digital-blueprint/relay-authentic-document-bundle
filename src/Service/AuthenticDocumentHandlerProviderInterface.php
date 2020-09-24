<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Service;

use DBP\API\CoreBundle\Entity\Person;

interface AuthenticDocumentHandlerProviderInterface
{
    public function persistAuthenticDocument(Person $person, string $documentType, string $documentData);

    public function handleAuthenticDocumentFetchException(Person $person, string $documentType, string $message);

    public function handleAuthenticDocumentNotAvailable(Person $person, string $documentType);
}
