<?php

declare(strict_types=1);

namespace DBP\API\EgizImageBundle\MessageHandler;

use DBP\API\EgizImageBundle\Message\EgizImageRequest;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class EgizImageRequestHandler implements MessageHandlerInterface
{
    public function __invoke(EgizImageRequest $message)
    {
        // TODO: Check at egiz server if image is already available
        dump($message);

        // TODO: If image is available persist it and notify the user

        // TODO: If image is not available dispatch a new delayed message
    }
}
