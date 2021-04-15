<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpAuthenticDocumentExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        // https://symfony.com/doc/4.4/messenger.html#transports-async-queued-messages
        $this->extendArrayParameter($container, 'dbp_api.messenger_routing', [
            'DBP\API\AuthenticDocumentBundle\Message\AuthenticDocumentRequestMessage' => 'async',
        ]);

        // Used in the data providers
        $this->extendArrayParameter(
            $container, 'dbp_api.allow_headers', ['Token']
        );
    }

    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $this->extendArrayParameter($container, 'dbp_api.paths_to_hide', [
            '/authentic_documents',
            '/authentic_document_requests',
            '/authentic_document_requests/{identifier}',
        ]);

        $this->extendArrayParameter(
            $container, 'api_platform.resource_class_directories', [__DIR__.'/../Entity']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yaml');

        $container->setParameter('dbp_api.authenticdocument.config', $mergedConfig);
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
