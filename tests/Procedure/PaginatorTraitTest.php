<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle\Tests\Procedure;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPCPaginatorBundle\Procedure\PaginatorTrait;

/**
 * PaginatorTrait 的单元测试.
 *
 * @internal
 */
#[CoversClass(PaginatorTrait::class)]
final class PaginatorTraitTest extends TestCase
{
    private TestPaginator $paginator;

    /** @var PaginatorInterface&MockObject */
    private PaginatorInterface $knpPaginator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->knpPaginator = $this->createMock(PaginatorInterface::class);
        $this->paginator = new TestPaginator();
        $this->paginator->paginator = $this->knpPaginator;
    }

    /**
     * 测试 getDefaultPageSize 方法的默认行为.
     */
    public function testGetDefaultPageSizeDefaultBehavior(): void
    {
        $pageSize = 25;
        $result = $this->paginator->publicGetDefaultPageSize($pageSize);
        $this->assertSame($pageSize, $result, '默认行为应返回相同的页大小值');
    }

    /**
     * 测试 getDefaultPageSize 方法的自定义行为.
     */
    public function testGetDefaultPageSizeCustomBehavior(): void
    {
        // 创建一个自定义的 TestPaginator，覆盖 getDefaultPageSize 方法
        $customPaginator = new class extends TestPaginator {
            protected function getDefaultPageSize(int $prevValue): int
            {
                return $prevValue * 2; // 返回两倍的值
            }
        };
        $customPaginator->paginator = $this->knpPaginator;

        $pageSize = 25;
        $result = $customPaginator->publicGetDefaultPageSize($pageSize);
        $this->assertSame($pageSize * 2, $result, '自定义行为应返回两倍的页大小值');
    }

    /**
     * 测试基本分页功能.
     */
    public function testFetchListBasicPagination(): void
    {
        // 使用 Query 具体类的原因：
        // 1. KnpPaginator 的 paginate 方法直接接受 Query 对象，需要的对象有具体的实现
        // 2. Query 类的方法在分页过程中被直接调用，需要类的完整行为
        // 3. 测试需要验证 PaginatorTrait 与 Query 类的集成交互方式
        $query = $this->createMock(Query::class);

        // 创建一个真实的MockPagination对象而不是模拟接口
        $items = [['id' => 1], ['id' => 2], ['id' => 3]];
        $pagination = new MockPagination($items, 1, 10, 25);

        // 配置 KnpPaginator 模拟
        $this->knpPaginator
            ->expects($this->once())
            ->method('paginate')
            ->with($query, 1, 10)
            ->willReturn($pagination)
        ;

        // 设置格式化函数
        $formatter = function ($item) {
            return $item;
        };

        // 执行方法
        $result = $this->paginator->publicFetchList($query, $formatter);

        // 断言
        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertCount(3, $result['list']);
        $this->assertSame(1, $result['pagination']['current']);
        $this->assertSame(10, $result['pagination']['pageSize']);
        $this->assertSame(25, $result['pagination']['total']);
        $this->assertTrue($result['pagination']['hasMore']);
    }

    /**
     * 测试当前页是最后一页时 hasMore 标志为 false.
     */
    public function testFetchListLastPage(): void
    {
        // 使用 Query 具体类的原因：
        // 1. KnpPaginator 的 paginate 方法直接接受 Query 对象，需要的对象有具体的实现
        // 2. Query 类的方法在分页过程中被直接调用，需要类的完整行为
        // 3. 测试需要验证 PaginatorTrait 与 Query 类的集成交互方式
        $query = $this->createMock(Query::class);

        // 创建模拟分页对象，表示最后一页
        $items = [['id' => 21], ['id' => 22], ['id' => 23], ['id' => 24], ['id' => 25]];
        $pagination = new MockPagination($items, 3, 10, 25);

        // 配置 KnpPaginator 模拟
        $this->knpPaginator
            ->method('paginate')
            ->willReturn($pagination)
        ;

        // 执行方法
        $result = $this->paginator->publicFetchList($query, fn ($item) => $item);

        // 断言 hasMore 为 false，因为这是最后一页
        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertFalse($result['pagination']['hasMore']);
    }

    /**
     * 测试处理可迭代结果.
     */
    public function testFetchListWithTraversableResults(): void
    {
        // 使用 Query 具体类的原因：
        // 1. KnpPaginator 的 paginate 方法直接接受 Query 对象，需要的对象有具体的实现
        // 2. Query 类的方法在分页过程中被直接调用，需要类的完整行为
        // 3. 测试需要验证 PaginatorTrait 与 Query 类的集成交互方式
        $query = $this->createMock(Query::class);

        // 原始数据项
        $items = [['id' => 1], ['id' => 2]];
        $pagination = new MockPagination($items, 1, 10, 25);

        $this->knpPaginator->method('paginate')->willReturn($pagination);

        // 返回可遍历结果的格式化函数
        $formatter = function ($item) {
            /** @var array{id: int} $item */
            // 创建一个 ArrayIterator 来模拟 Traversable 接口
            return new \ArrayIterator([
                ['name' => 'Item ' . $item['id'] . '-1'],
                ['name' => 'Item ' . $item['id'] . '-2'],
            ]);
        };

        $result = $this->paginator->publicFetchList($query, $formatter);

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
        // 使用 Query 具体类的原因：
        // 1. KnpPaginator 的 paginate 方法直接接受 Query 对象，需要的对象有具体的实现
        // 2. Query 类的方法在分页过程中被直接调用，需要类的完整行为
        // 3. 测试需要验证 PaginatorTrait 与 Query 类的集成交互方式
        $query = $this->createMock(Query::class);

        // 原始数据项
        $items = [1, 2, 3];
        $pagination = new MockPagination($items, 1, 10, 25);

        $this->knpPaginator->method('paginate')->willReturn($pagination);

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

        $result = $this->paginator->publicFetchList($query, $formatter);

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
        // 使用 QueryBuilder 具体类的原因：
        // 1. KnpPaginator 的 paginate 方法直接接受 QueryBuilder 对象，需要的对象有具体的实现
        // 2. QueryBuilder 类的方法在分页过程中被直接谂用，需要类的完整行为
        // 3. 测试需要验证 PaginatorTrait 与 QueryBuilder 类的集成交互方式
        $query = $this->createMock(QueryBuilder::class);

        // 原始数据项
        $items = [['id' => 1], ['id' => 2]];
        $pagination = new MockPagination($items, 1, 10, 99);

        $this->knpPaginator->method('paginate')->willReturn($pagination);

        // 自定义计数器函数
        $counter = function ($query, $pagination) {
            // 返回自定义总数，忽略分页组件的值
            return 50;
        };

        $result = $this->paginator->publicFetchList(
            $query,
            fn ($item) => $item,
            $counter
        );

        // 应使用自定义计数器返回的总数
        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertSame(50, $result['pagination']['total']);
    }

    /**
     * 测试使用 QueryBuilder 作为参数.
     */
    public function testFetchListWithQueryBuilder(): void
    {
        // 使用 QueryBuilder 具体类的原因：
        // 1. KnpPaginator 的 paginate 方法直接接受 QueryBuilder 对象，需要的对象有具体的实现
        // 2. QueryBuilder 类的方法在分页过程中被直接谂用，需要类的完整行为
        // 3. 测试需要验证 PaginatorTrait 与 QueryBuilder 类的集成交互方式
        $queryBuilder = $this->createMock(QueryBuilder::class);

        // 原始数据项
        $items = [['id' => 1], ['id' => 2]];
        $pagination = new MockPagination($items, 1, 10, 25);

        $this->knpPaginator
            ->expects($this->once())
            ->method('paginate')
            ->with($queryBuilder, 1, 10)
            ->willReturn($pagination)
        ;

        $result = $this->paginator->publicFetchList($queryBuilder, fn ($item) => $item);

        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertCount(2, $result['list']);
    }

    /**
     * 测试自定义页大小.
     */
    public function testFetchListWithCustomPageSize(): void
    {
        // 使用 Query 具体类的原因：
        // 1. KnpPaginator 的 paginate 方法直接接受 Query 对象，需要的对象有具体的实现
        // 2. Query 类的方法在分页过程中被直接调用，需要类的完整行为
        // 3. 测试需要验证 PaginatorTrait 与 Query 类的集成交互方式
        $query = $this->createMock(Query::class);

        // 创建一个自定义的 TestPaginator 覆盖 getDefaultPageSize
        $customPaginator = new class extends TestPaginator {
            protected function getDefaultPageSize(int $prevValue): int
            {
                return 20; // 始终返回 20，不考虑输入值
            }
        };
        $customPaginator->paginator = $this->knpPaginator;
        $customPaginator->pageSize = 10; // 设置初始页大小为 10

        // 原始数据项
        $items = [['id' => 1]];
        $pagination = new MockPagination($items, 1, 20, 50);

        // KnpPaginator 应该使用自定义的页大小
        $this->knpPaginator
            ->expects($this->once())
            ->method('paginate')
            ->with($query, 1, 20) // 页大小应该是 20，不是 10
            ->willReturn($pagination)
        ;

        $result = $customPaginator->publicFetchList($query, fn ($item) => $item);

        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertSame(20, $result['pagination']['pageSize']);
    }

    /**
     * 测试 emptyList 方法的默认值
     */
    public function testEmptyListDefaultValues(): void
    {
        $result = $this->paginator->publicEmptyList();

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
        $this->paginator->currentPage = 3;
        $this->paginator->pageSize = 25;

        $result = $this->paginator->publicEmptyList();

        /** @var array{list: list<mixed>, pagination: array{current: int, pageSize: int, total: int, hasMore: bool}} $result */
        $this->assertEmpty($result['list']);
        $this->assertSame(3, $result['pagination']['current']);
        $this->assertSame(25, $result['pagination']['pageSize']);
        $this->assertSame(0, $result['pagination']['total']);
        $this->assertFalse($result['pagination']['hasMore']);
    }
}
