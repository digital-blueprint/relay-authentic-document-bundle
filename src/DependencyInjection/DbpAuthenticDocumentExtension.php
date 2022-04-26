<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DependencyInjection;

use DBP\API\AuthenticDocumentBundle\Message\AuthenticDocumentRequestMessage;
use Dbp\Relay\CoreBundle\Extension\ExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpAuthenticDocumentExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    use ExtensionTrait;

    public function prepend(ContainerBuilder $container)
    {
        $this->addQueueMessage($container, AuthenticDocumentRequestMessage::class);
        // Used in the data providers
        $this->addAllowHeader($container, 'Token');
    }

    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $pathsToHide = [
            '/authentic-documents',
            '/authentic-document-requests',
            '/authentic-document-requests/{identifier}',
        ];

        foreach ($pathsToHide as $path) {
            $this->addPathToHide($container, $path);
        }

        $this->addResourceClassDirectory($container, __DIR__.'/../Entity');

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yaml');

        $definition = $container->getDefinition('DBP\API\AuthenticDocumentBundle\Service\AuthenticDocumentApi');
        $definition->addMethodCall('setConfig', [$mergedConfig]);
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
