<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\Service;

class TokenInformation
{
    /**
     * @var \DateTime
     */
    public $birthDate;

    /**
     * @var string
     */
    public $givenName;

    /**
     * @var string
     */
    public $familyName;

    /**
     * @var string
     */
    public $subject;
}
