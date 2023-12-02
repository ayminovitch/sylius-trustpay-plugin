<?php

declare(strict_types=1);

namespace Ayminovitch\TrustpayPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ayminovitch_trustpay_plugin');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
