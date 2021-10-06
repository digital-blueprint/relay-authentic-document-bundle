<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\TestUtils;

use DBP\API\AuthenticDocumentBundle\API\DocumentStorageInterface;
use Dbp\Relay\BaseBundle\Entity\Person;

class DummyDocumentStorage implements DocumentStorageInterface
{
    public function storeDocument(Person $person, \DateTimeInterface $requestCreatedDate, string $documentType, string $documentData): void
    {
    }

    public function canStoreDocument(Person $person, string $documentType): bool
    {
        return true;
    }

    public function storeDocumentError(Person $person, \DateTimeInterface $requestCreatedDate, string $documentType, string $message): void
    {
    }

    public function storeDocumentNotAvailable(Person $person, \DateTimeInterface $requestCreatedDate, string $documentType): void
    {
    }
}
