<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\UCardPhoto;

class UCard
{
    private $ident;
    private $condentId;
    private $condentSize;
    private $isUpdatable;
    private $cardType;

    public function __construct()
    {
        // IDENT_NR_OBFUSCATED
        $this->ident = '';
        // CONTENT_ID
        $this->condentId = '';
        // CONTENT_SIZE
        $this->condentSize = '';
        // IS_UPDATABLE
        $this->isUpdatable = '';
        // CARD_TYPE
        $this->cardType = '';
    }
}
