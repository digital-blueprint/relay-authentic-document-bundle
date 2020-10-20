<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DbpAuthenticDocumentBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->loadFromExtension('framework', [
            'mailer' => [
                'dsn' => '%env(MAILER_DSN)%'
            ]
        ]);
    }
}
