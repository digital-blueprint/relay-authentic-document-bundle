<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\API;

use Dbp\Relay\BasePersonBundle\Entity\Person;

interface DocumentStorageInterface
{
    /**
     * @throws DocumentStorageException
     */
    public function storeDocument(Person $person, \DateTimeInterface $requestCreatedDate, string $documentType, string $documentData): void;

    /**
     * @throws DocumentStorageException
     */
    public function canStoreDocument(Person $person, string $documentType): bool;

    /**
     * @throws DocumentStorageException
     */
    public function storeDocumentError(Person $person, \DateTimeInterface $requestCreatedDate, string $documentType, string $message): void;

    /**
     * @throws DocumentStorageException
     */
    public function storeDocumentNotAvailable(Person $person, \DateTimeInterface $requestCreatedDate, string $documentType): void;
}
