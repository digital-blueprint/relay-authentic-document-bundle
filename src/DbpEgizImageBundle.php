<?php

declare(strict_types=1);

namespace DBP\API\EgizImageBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DbpEgizImageBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->loadFromExtension('framework', [
            'messenger' => [
                'transports' => [
                    'async' => '%env(MESSENGER_TRANSPORT_DSN)%',
                ],
                'routing' => [
                    'DBP\API\EgizImageBundle\Message\EgizImageRequest' => 'async',
                ],
            ],
        ]);
    }
}
