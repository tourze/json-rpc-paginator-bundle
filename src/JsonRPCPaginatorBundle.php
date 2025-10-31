<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle;

use Knp\Bundle\PaginatorBundle\KnpPaginatorBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class JsonRPCPaginatorBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            KnpPaginatorBundle::class => ['all' => true],
        ];
    }
}
