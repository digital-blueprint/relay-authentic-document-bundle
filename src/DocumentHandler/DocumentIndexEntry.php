<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DocumentHandler;

class DocumentIndexEntry
{
    /**
     * @var string
     */
    public $urlsafeAttribute;

    /**
     * @var string
     */
    public $availabilityStatus;

    /**
     * @var ?string
     */
    public $documentToken;

    /**
     * @var ?\DateTimeInterface
     */
    public $expires;

    /**
     * @var ?\DateTimeInterface
     */
    public $eta;

    /**
     * @var ?string
     */
    public $errorMessage;
}
