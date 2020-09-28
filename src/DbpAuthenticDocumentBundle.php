<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DbpAuthenticDocumentBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        if (($_ENV['MESSENGER_USE_ASYNC'] ?? 'false') === 'true') {
            // https://symfony.com/doc/4.4/messenger.html#transports-async-queued-messages
            $container->loadFromExtension('framework', [
                'messenger' => [
                    'transports' => [
                        'async' => '%env(MESSENGER_TRANSPORT_DSN)%',
                    ],
                    'routing' => [
                        'DBP\API\AuthenticDocumentBundle\Message\AuthenticDocumentRequestMessage' => 'async',
                    ],
                ],
            ]);
        }

        $container->loadFromExtension('framework', [
            'mailer' => [
                'dsn' => '%env(MAILER_DSN)%'
            ]
        ]);
    }
}
