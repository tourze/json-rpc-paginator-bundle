<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle\Tests\Procedure;

use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPCPaginatorBundle\Procedure\PaginatorTrait;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * PaginatorTrait 的集成测试.
 *
 * @internal
 */
#[CoversClass(PaginatorTrait::class)]
#[RunTestsInSeparateProcesses]
final class PaginatorTraitTest extends AbstractIntegrationTestCase
{
    private TestPaginatorService $service;

    protected function onSetUp(): void
    {
        // 创建测试服务并注入真实的 PaginatorInterface
        $this->service = new TestPaginatorService();
        $this->service->paginator = self::getService(PaginatorInterface::class);
    }

    /**
     * 测试 getDefaultPageSize 方法的默认行为.
     */
    public function testGetDefaultPageSizeDefaultBehavior(): void
    {
        $pageSize = 25;
        $result = $this->service->publicGetDefaultPageSize($pageSize);
        $this->assertSame($pageSize, $result, '默认行为应返回相同的页大小值');
    }

    /**
     * 测试 getDefaultPageSize 方法的自定义行为.
     */
    public function testGetDefaultPageSizeCustomBehavior(): void
    {
        // 创建一个自定义的服务，覆盖 getDefaultPageSize 方法
        $customService = new class extends TestPaginatorService {
            protected function getDefaultPageSize(int $prevValue): int
            {
                return $prevValue * 2; // 返回两倍的值
            }
        };
        $customService->paginator = self::getService(PaginatorInterface::class);

        $pageSize = 25;
        $result = $customService->publicGetDefaultPageSize($pageSize);
        $this->assertSame($pageSize * 2, $result, '自定义行为应返回两倍的页大小值');
    }

    /**
     * 测试基本分页功能.
     */
    public function testFetchListBasicPagination(): void
    {
        // 准备测试数据
        $items = [['id' => 1], ['id' => 2], ['id' => 3]];

        // 设置格式化函数
        $formatter = function ($item) {
            return $item;
        };

        // 创建分页参数
        $param = new TestPaginatorParam(pageSize: 10, currentPage: 1);

        // 执行方法 - 使用数组作为目标（Knp Paginator 支持）
        $result = $this->service->publicFetchListFromArray($items, $formatter, null, $param);

        // 断言
        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertCount(3, $result['list']);
        $this->assertSame(1, $result['pagination']['current']);
        $this->assertSame(10, $result['pagination']['pageSize']);
        $this->assertSame(3, $result['pagination']['total']);
        $this->assertFalse($result['pagination']['hasMore']); // 3 项在第 1 页，无更多内容
    }

    /**
     * 测试当前页是最后一页时 hasMore 标志为 false.
     */
    public function testFetchListLastPage(): void
    {
        // 准备测试数据 - 25 项，每页 10 项，第 3 页（最后一页）
        $items = range(1, 25);

        // 创建分页参数
        $param = new TestPaginatorParam(pageSize: 10, currentPage: 3);

        // 执行方法
        $result = $this->service->publicFetchListFromArray($items, fn ($item) => ['id' => $item], null, $param);

        // 断言 hasMore 为 false，因为这是最后一页
        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertFalse($result['pagination']['hasMore']);
        $this->assertCount(5, $result['list']); // 最后一页只有 5 项
    }

    /**
     * 测试处理可迭代结果.
     */
    public function testFetchListWithTraversableResults(): void
    {
        // 准备测试数据
        $items = [['id' => 1], ['id' => 2]];

        // 返回可遍历结果的格式化函数
        $formatter = function ($item) {
            /** @var array{id: int} $item */
            // 创建一个 ArrayIterator 来模拟 Traversable 接口
            return new \ArrayIterator([
                ['name' => 'Item ' . $item['id'] . '-1'],
                ['name' => 'Item ' . $item['id'] . '-2'],
            ]);
        };

        // 创建分页参数
        $param = new TestPaginatorParam(pageSize: 10, currentPage: 1);

        $result = $this->service->publicFetchListFromArray($items, $formatter, null, $param);

        // 每个原始项都应生成 2 个格式化项，总共应有 4 个项
        /** @var array{list: list<array{name: string}>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertCount(4, $result['list']);
        $this->assertSame('Item 1-1', $result['list'][0]['name']);
        $this->assertSame('Item 1-2', $result['list'][1]['name']);
        $this->assertSame('Item 2-1', $result['list'][2]['name']);
        $this->assertSame('Item 2-2', $result['list'][3]['name']);
    }

    /**
     * 测试忽略非数组结果.
     */
    public function testFetchListWithNonArrayResults(): void
    {
        // 准备测试数据
        $items = [1, 2, 3];

        // 返回非数组结果的格式化函数（如字符串或 null）
        $formatter = function ($item) {
            if (1 === $item) {
                return ['id' => $item]; // 返回有效数组
            }
            if (2 === $item) {
                return 'string result'; // 返回字符串，应被忽略
            }

            return null; // 返回 null，应被忽略
        };

        // 创建分页参数
        $param = new TestPaginatorParam(pageSize: 10, currentPage: 1);

        $result = $this->service->publicFetchListFromArray($items, $formatter, null, $param);

        // 只有第一项应该被添加到列表中
        /** @var array{list: list<array{id: int}>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertCount(1, $result['list']);
        $this->assertSame(['id' => 1], $result['list'][0]);
    }

    /**
     * 测试自定义计数器回调.
     */
    public function testFetchListWithCustomCounter(): void
    {
        // 准备测试数据
        $items = [['id' => 1], ['id' => 2]];

        // 自定义计数器函数
        $counter = function ($query, $pagination) {
            // 返回自定义总数，忽略分页组件的值
            return 50;
        };

        // 创建分页参数
        $param = new TestPaginatorParam(pageSize: 10, currentPage: 1);

        $result = $this->service->publicFetchListFromArray(
            $items,
            fn ($item) => $item,
            $counter,
            $param
        );

        // 应使用自定义计数器返回的总数
        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertSame(50, $result['pagination']['total']);
    }

    /**
     * 测试自定义页大小.
     */
    public function testFetchListWithCustomPageSize(): void
    {
        // 准备测试数据
        $items = [['id' => 1]];

        // 创建一个自定义的服务覆盖 getDefaultPageSize
        $customService = new class extends TestPaginatorService {
            protected function getDefaultPageSize(int $prevValue): int
            {
                return 20; // 始终返回 20，不考虑输入值
            }
        };
        $customService->paginator = self::getService(PaginatorInterface::class);

        // 创建分页参数，初始页大小为 10
        $param = new TestPaginatorParam(pageSize: 10, currentPage: 1);

        $result = $customService->publicFetchListFromArray($items, fn ($item) => $item, null, $param);

        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertSame(20, $result['pagination']['pageSize']);
    }

    /**
     * 测试 emptyList 方法的默认值
     */
    public function testEmptyListDefaultValues(): void
    {
        // 创建默认分页参数
        $param = new TestPaginatorParam();

        $result = $this->service->publicEmptyList($param);

        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertEmpty($result['list']);
        $this->assertSame(1, $result['pagination']['current']);
        $this->assertSame(10, $result['pagination']['pageSize']);
        $this->assertSame(0, $result['pagination']['total']);
        $this->assertFalse($result['pagination']['hasMore']);
    }

    /**
     * 测试 emptyList 方法的自定义值
     */
    public function testEmptyListCustomValues(): void
    {
        // 设置自定义分页参数
        $param = new TestPaginatorParam(pageSize: 25, currentPage: 3);

        $result = $this->service->publicEmptyList($param);

        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertEmpty($result['list']);
        $this->assertSame(3, $result['pagination']['current']);
        $this->assertSame(25, $result['pagination']['pageSize']);
        $this->assertSame(0, $result['pagination']['total']);
        $this->assertFalse($result['pagination']['hasMore']);
    }

    /**
     * 测试向后兼容：不传入 param 时使用默认值
     */
    public function testBackwardCompatibilityWithoutParam(): void
    {
        // 准备测试数据
        $items = [['id' => 1]];

        // 不传入 param
        $result = $this->service->publicFetchListFromArray($items, fn ($item) => $item);

        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertCount(1, $result['list']);
        $this->assertSame(1, $result['pagination']['current']);
        $this->assertSame(10, $result['pagination']['pageSize']);
    }

    /**
     * 测试多页数据的 hasMore 标志
     */
    public function testFetchListHasMoreWithMultiplePages(): void
    {
        // 准备测试数据 - 25 项
        $items = range(1, 25);

        // 创建分页参数 - 第 1 页，每页 10 项
        $param = new TestPaginatorParam(pageSize: 10, currentPage: 1);

        $result = $this->service->publicFetchListFromArray($items, fn ($item) => ['id' => $item], null, $param);

        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertTrue($result['pagination']['hasMore']); // 还有第 2、3 页
        $this->assertCount(10, $result['list']);
        $this->assertSame(25, $result['pagination']['total']);
    }
}
