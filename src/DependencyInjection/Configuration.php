<?php

declare(strict_types=1);

namespace DBP\API\AuthenticDocumentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('dbp_authentic_document');

        $treeBuilder->getRootNode()
                ->children()
                    ->scalarNode('co_oauth2_api_client_id')->end()
                    ->scalarNode('co_oauth2_api_client_secret')->end()
                    ->scalarNode('co_oauth2_api_api_url')->end()
                    ->scalarNode('dhandler_idp_url')->end()
                    ->scalarNode('dhandler_api_url')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
