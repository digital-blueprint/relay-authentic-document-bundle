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
    private $typeId;

    /**
     * @var \DateTime
     */
    private $estimatedResponseDate;

    /**
     * @var \DateTime
     */
    private $requestCreatedDate;

    /**
     * @var int
     */
    private $retry = 0;

    /**
     * AuthenticDocumentRequestMessage constructor.
     * @param $person Person|null
     * @param $documentToken
     * @param $typeId
     * @param \DateTime $requestCreatedDate
     * @param \DateTime $estimatedResponseDate
     * @param int $retry
     */
    public function __construct(
        ?Person $person,
        $documentToken,
        $typeId,
        \DateTime $requestCreatedDate,
        \DateTime $estimatedResponseDate,
        int $retry = 0
    )
    {
        $this->person = $person;
        $this->documentToken = $documentToken;
        $this->typeId = $typeId;
        $this->requestCreatedDate = $requestCreatedDate;
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

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function getEstimatedResponseDate(): \DateTime
    {
        return $this->estimatedResponseDate;
    }

    public function getRequestCreatedDate(): \DateTime
    {
        return $this->requestCreatedDate;
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

    /**
     * @param \DateTime $estimatedResponseDate
     * @return AuthenticDocumentRequestMessage
     */
    public function setEstimatedResponseDate(\DateTime $estimatedResponseDate): self
    {
        $this->estimatedResponseDate = $estimatedResponseDate;

        return $this;
    }
}
