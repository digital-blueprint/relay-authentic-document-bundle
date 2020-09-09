<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Message;

use DBP\API\CoreBundle\Entity\Person;

class AuthenticDocumentRequestMessage
{
    /**
     * @var Person|null
     */
    private $person;

    /**
     * @var string
     */
    private $documentToken;

    /**
     * @var string
     */
    private $urlAttribute;

    /**
     * @var \DateTime
     */
    private $estimatedResponseDate;

    /**
     * @var int
     */
    private $retry = 0;

    public function __construct($person, $documentToken, $urlAttribute, \DateTime $estimatedResponseDate, int $retry = 0)
    {
        $this->person = $person;
        $this->documentToken = $documentToken;
        $this->urlAttribute = $urlAttribute;
        $this->estimatedResponseDate = $estimatedResponseDate;
        $this->retry = $retry;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function getDocumentToken(): string
    {
        return $this->documentToken;
    }

    public function getUrlAttribute(): string
    {
        return $this->urlAttribute;
    }

    public function getEstimatedResponseDate(): \DateTime
    {
        return $this->estimatedResponseDate;
    }

    public function getRetry(): int
    {
        return $this->retry;
    }

    public function setRetry(int $retry): void
    {
        $this->retry = $retry;
    }

    public function incRetry(): void
    {
        ++$this->retry;
    }
}
