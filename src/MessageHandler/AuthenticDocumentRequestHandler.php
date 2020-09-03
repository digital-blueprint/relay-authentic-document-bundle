<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\MessageHandler;

use DBP\API\AuthenticDocumentBundle\Message\AuthenticDocumentRequestMessage;
use DBP\API\AuthenticDocumentBundle\Service\AuthenticDocumentApi;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class AuthenticDocumentRequestHandler implements MessageHandlerInterface
{
    private $api;

    public function __construct(AuthenticDocumentApi $api)
    {
        $this->api = $api;
    }

    public function __invoke(AuthenticDocumentRequestMessage $message)
    {
        $this->api->handleRequestMessage($message);
    }
}
