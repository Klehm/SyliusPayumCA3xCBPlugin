<?php

declare(strict_types=1);

namespace Klehm\SyliusPayumCA3xcbPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('klehm_sylius_payum_ca3xcb_plugin');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
