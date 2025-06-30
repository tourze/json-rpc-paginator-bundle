<?php

namespace Tourze\JsonRPCPaginatorBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\JsonRPCPaginatorBundle\JsonRPCPaginatorBundle;

class JsonRPCPaginatorBundleTest extends TestCase
{
    public function testBundleIsInstantiable(): void
    {
        $bundle = new JsonRPCPaginatorBundle();
        $this->assertInstanceOf(JsonRPCPaginatorBundle::class, $bundle);
    }

    public function testBundleImplementsBundleDependencyInterface(): void
    {
        $bundle = new JsonRPCPaginatorBundle();
        $this->assertInstanceOf(BundleDependencyInterface::class, $bundle);
    }

    public function testGetBundleDependenciesReturnsExpectedArray(): void
    {
        $dependencies = JsonRPCPaginatorBundle::getBundleDependencies();
        
        $this->assertArrayHasKey(\Knp\Bundle\PaginatorBundle\KnpPaginatorBundle::class, $dependencies);
        $this->assertEquals(['all' => true], $dependencies[\Knp\Bundle\PaginatorBundle\KnpPaginatorBundle::class]);
    }
}