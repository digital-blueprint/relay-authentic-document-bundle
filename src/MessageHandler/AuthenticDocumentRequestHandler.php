<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\MessageHandler;

use DBP\API\AuthenticDocumentBundle\Message\AuthenticDocumentRequestMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class AuthenticDocumentRequestHandler implements MessageHandlerInterface
{
    public function __invoke(AuthenticDocumentRequestMessage $message)
    {
        // TODO: Check at egiz server if image is already available
        dump($message);

        // TODO: If image is available persist it and notify the user

        // TODO: If image is not available dispatch a new delayed message
    }
}
