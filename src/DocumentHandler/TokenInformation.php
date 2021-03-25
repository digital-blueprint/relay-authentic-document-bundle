<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DocumentHandler;

class TokenInformation
{
    /**
     * @var string
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
