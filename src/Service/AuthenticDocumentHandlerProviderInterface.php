<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Service;

use DBP\API\CoreBundle\Entity\Person;

interface AuthenticDocumentHandlerProviderInterface
{
    public function persistAuthenticDocument(Person $person, string $documentType, string $documentData);
}
