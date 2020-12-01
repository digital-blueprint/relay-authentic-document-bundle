<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DbpAuthenticDocumentExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        // https://symfony.com/doc/4.4/messenger.html#transports-async-queued-messages
        $this->extendArrayParameter($container, 'dbp_api.messenger_routing', [
            'DBP\API\AuthenticDocumentBundle\Message\AuthenticDocumentRequestMessage' => 'async',
        ]);
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $this->extendArrayParameter($container, 'dbp_api.paths_to_hide', [
            '/authentic_documents',
            '/authentic_document_requests',
            '/authentic_document_requests/{id}',
        ]);

        $this->extendArrayParameter(
            $container, 'api_platform.resource_class_directories', [__DIR__.'/../Entity']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yaml');
    }

    private function extendArrayParameter(ContainerBuilder $container, string $parameter, array $values)
    {
        if (!$container->hasParameter($parameter)) {
            $container->setParameter($parameter, []);
        }
        $oldValues = $container->getParameter($parameter);
        assert(is_array($oldValues));
        $container->setParameter($parameter, array_merge($oldValues, $values));
    }
}
