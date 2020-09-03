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
     * @var \DateTime
     */
    private $estimatedResponseDate;

    private $retry = 0;

    public function __construct(Person $person, \DateTime $estimatedResponseDate, int $retry = 0)
    {
        $this->person = $person;
        $this->estimatedResponseDate = $estimatedResponseDate;
        $this->retry = $retry;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
