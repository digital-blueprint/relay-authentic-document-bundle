<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Message;

use DBP\API\CoreBundle\Entity\Person;

class AuthenticDocumentRequestMessage
{
    /**
     * @var Person
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

    public function __construct(Person $person, $documentToken, $urlAttribute, \DateTime $estimatedResponseDate, int $retry = 0)
    {
        $this->person = $person;
        $this->documentToken = $documentToken;
        $this->urlAttribute = $urlAttribute;
        $this->estimatedResponseDate = $estimatedResponseDate;
        $this->retry = $retry;
    }

    /**
     * @return Person
     */
    public function getPerson(): Person
    {
        return $this->person;
    }

    /**
     * @return string
     */
    public function getDocumentToken(): string
    {
        return $this->documentToken;
    }

    /**
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return $this->urlAttribute;
    }

    /**
     * @return \DateTime
     */
    public function getEstimatedResponseDate(): \DateTime
    {
        return $this->estimatedResponseDate;
    }

    /**
     * @return int
     */
    public function getRetry(): int
    {
        return $this->retry;
    }
}
