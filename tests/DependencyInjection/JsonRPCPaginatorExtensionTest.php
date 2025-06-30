<?php

namespace Tourze\JsonRPCPaginatorBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\JsonRPCPaginatorBundle\DependencyInjection\JsonRPCPaginatorExtension;

/**
 * JsonRPCPaginatorExtension 的单元测试
 */
class JsonRPCPaginatorExtensionTest extends TestCase
{
    private JsonRPCPaginatorExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new JsonRPCPaginatorExtension();
        $this->container = new ContainerBuilder();
    }

    /**
     * 测试基本加载功能
     */
    public function testLoad(): void
    {
        $this->extension->load([], $this->container);

        // 验证 knp_paginator 配置已被添加
        $configs = $this->container->getExtensionConfig('knp_paginator');
        $this->assertNotEmpty($configs);
    }

    /**
     * 测试当 knp_paginator 扩展不存在时的配置
     */
    public function testLoadWithoutKnpPaginatorExtension(): void
    {
        $this->extension->load([], $this->container);

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
        $this->assertEquals('@KnpPaginator/Pagination/sliding.html.twig', $templates['pagination']);
        $this->assertEquals('@KnpPaginator/Pagination/rel_links.html.twig', $templates['rel_links']);
        $this->assertEquals('@KnpPaginator/Pagination/sortable_link.html.twig', $templates['sortable']);
        $this->assertEquals('@KnpPaginator/Pagination/filtration.html.twig', $templates['filtration']);
    }

    /**
     * 测试当 knp_paginator 扩展已存在时的配置
     */
    public function testLoadWithExistingKnpPaginatorExtension(): void
    {
        // 模拟已经存在 knp_paginator 扩展
        $this->container->prependExtensionConfig('knp_paginator', [
            'page_range' => 10
        ]);
        
        $initialConfigCount = count($this->container->getExtensionConfig('knp_paginator'));
        
        $this->extension->load([], $this->container);
        
        // 验证配置没有被重复添加
        $finalConfigCount = count($this->container->getExtensionConfig('knp_paginator'));
        $this->assertEquals($initialConfigCount + 1, $finalConfigCount);
    }
}