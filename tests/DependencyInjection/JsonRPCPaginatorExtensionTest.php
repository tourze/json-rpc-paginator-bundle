<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Tourze\JsonRPCPaginatorBundle\DependencyInjection\JsonRPCPaginatorExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * JsonRPCPaginatorExtension 的单元测试.
 *
 * @internal
 */
#[CoversClass(JsonRPCPaginatorExtension::class)]
final class JsonRPCPaginatorExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    /**
     * 测试当 knp_paginator 扩展不存在时的配置.
     */
    public function testPrependWithoutKnpPaginatorExtension(): void
    {
        $extension = new JsonRPCPaginatorExtension();
        $extension->prepend($this->container);

        // 验证预期的配置被添加
        $configs = $this->container->getExtensionConfig('knp_paginator');

        $this->assertNotEmpty($configs);
        $expectedConfig = $configs[0];

        // 验证基本配置
        $this->assertFalse($expectedConfig['convert_exception']);
        $this->assertEquals(5, $expectedConfig['page_range']);
        $this->assertFalse($expectedConfig['remove_first_page_param']);

        // 验证默认选项
        $defaultOptions = $expectedConfig['default_options'];
        $this->assertIsArray($defaultOptions);
        $this->assertEquals('page', $defaultOptions['page_name']);
        $this->assertEquals('sort', $defaultOptions['sort_field_name']);
        $this->assertEquals('direction', $defaultOptions['sort_direction_name']);
        $this->assertTrue($defaultOptions['distinct']);
        $this->assertEquals('filterField', $defaultOptions['filter_field_name']);
        $this->assertEquals('filterValue', $defaultOptions['filter_value_name']);
        $this->assertEquals('ignore', $defaultOptions['page_out_of_range']);
        $this->assertEquals(10, $defaultOptions['default_limit']);

        // 验证模板配置
        $templates = $expectedConfig['template'];
        $this->assertIsArray($templates);
        $this->assertEquals('@KnpPaginator/Pagination/sliding.html.twig', $templates['pagination']);
        $this->assertEquals('@KnpPaginator/Pagination/rel_links.html.twig', $templates['rel_links']);
        $this->assertEquals('@KnpPaginator/Pagination/sortable_link.html.twig', $templates['sortable']);
        $this->assertEquals('@KnpPaginator/Pagination/filtration.html.twig', $templates['filtration']);
    }

    /**
     * 测试当 knp_paginator 扩展已存在时的配置.
     */
    public function testPrependWithExistingKnpPaginatorExtension(): void
    {
        // 模拟已经存在 knp_paginator 扩展的情况
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        // 模拟 knp_paginator 扩展已注册
        $container->registerExtension(new class implements ExtensionInterface {
            public function getAlias(): string
            {
                return 'knp_paginator';
            }

            public function load(array $configs, ContainerBuilder $container): void
            {
                // Mock implementation
            }

            public function getNamespace(): string
            {
                return 'knp_paginator';
            }

            public function getXsdValidationBasePath(): string|false
            {
                return false;
            }
        });

        $initialConfigCount = count($container->getExtensionConfig('knp_paginator'));

        $extension = new JsonRPCPaginatorExtension();
        $extension->prepend($container);

        // 验证配置没有被添加（因为扩展已存在）
        $finalConfigCount = count($container->getExtensionConfig('knp_paginator'));
        $this->assertEquals($initialConfigCount, $finalConfigCount);
    }
}
