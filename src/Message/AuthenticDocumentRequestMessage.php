<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Message;

class AuthenticDocumentRequestMessage
{
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
     * @var int
     */
    private $retry = 0;

    public function __construct($documentToken, $typeId, \DateTime $estimatedResponseDate, int $retry = 0)
    {
        $this->documentToken = $documentToken;
        $this->typeId = $typeId;
        $this->estimatedResponseDate = $estimatedResponseDate;
        $this->retry = $retry;
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
