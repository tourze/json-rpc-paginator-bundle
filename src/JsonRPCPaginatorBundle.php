<?php

namespace Tourze\JsonRPCPaginatorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class JsonRPCPaginatorBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            \Knp\Bundle\PaginatorBundle\KnpPaginatorBundle::class => ['all' => true],
        ];
    }
}
