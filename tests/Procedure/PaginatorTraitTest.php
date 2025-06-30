<?php

namespace Tourze\JsonRPCPaginatorBundle\Tests\Procedure;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPCPaginatorBundle\Procedure\PaginatorTrait;

/**
 * PaginatorTrait 的单元测试
 */
class PaginatorTraitTest extends TestCase
{
    private TestPaginator $paginator;
    private PaginatorInterface $knpPaginator;

    protected function setUp(): void
    {
        $this->knpPaginator = $this->createMock(PaginatorInterface::class);
        $this->paginator = new TestPaginator();
        $this->paginator->paginator = $this->knpPaginator;
    }

    /**
     * 测试 getDefaultPageSize 方法的默认行为
     */
    public function testGetDefaultPageSize_defaultBehavior(): void
    {
        $pageSize = 25;
        $result = $this->paginator->publicGetDefaultPageSize($pageSize);
        $this->assertSame($pageSize, $result, '默认行为应返回相同的页大小值');
    }

    /**
     * 测试 getDefaultPageSize 方法的自定义行为
     */
    public function testGetDefaultPageSize_customBehavior(): void
    {
        // 创建一个自定义的 TestPaginator，覆盖 getDefaultPageSize 方法
        $customPaginator = new class() extends TestPaginator {
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
     * 测试基本分页功能
     */
    public function testFetchList_basicPagination(): void
    {
        $query = $this->createMock(Query::class);

        // 创建一个真实的MockPagination对象而不是模拟接口
        $items = [['id' => 1], ['id' => 2], ['id' => 3]];
        $pagination = new MockPagination($items, 1, 10, 25);

        // 配置 KnpPaginator 模拟
        $this->knpPaginator
            ->expects($this->once())
            ->method('paginate')
            ->with($query, 1, 10)
            ->willReturn($pagination);

        // 设置格式化函数
        $formatter = function ($item) {
            return $item;
        };

        // 执行方法
        $result = $this->paginator->publicFetchList($query, $formatter);

        // 断言
        $this->assertCount(3, $result['list']);
        $this->assertSame(1, $result['pagination']['current']);
        $this->assertSame(10, $result['pagination']['pageSize']);
        $this->assertSame(25, $result['pagination']['total']);
        $this->assertTrue($result['pagination']['hasMore']);
    }

    /**
     * 测试当前页是最后一页时 hasMore 标志为 false
     */
    public function testFetchList_lastPage(): void
    {
        $query = $this->createMock(Query::class);

        // 创建模拟分页对象，表示最后一页
        $items = [['id' => 21], ['id' => 22], ['id' => 23], ['id' => 24], ['id' => 25]];
        $pagination = new MockPagination($items, 3, 10, 25);

        // 配置 KnpPaginator 模拟
        $this->knpPaginator
            ->method('paginate')
            ->willReturn($pagination);

        // 执行方法
        $result = $this->paginator->publicFetchList($query, fn($item) => $item);

        // 断言 hasMore 为 false，因为这是最后一页
        $this->assertFalse($result['pagination']['hasMore']);
    }

    /**
     * 测试处理可迭代结果
     */
    public function testFetchList_withTraversableResults(): void
    {
        $query = $this->createMock(Query::class);

        // 原始数据项
        $items = [['id' => 1], ['id' => 2]];
        $pagination = new MockPagination($items, 1, 10, 25);

        $this->knpPaginator->method('paginate')->willReturn($pagination);

        // 返回可遍历结果的格式化函数
        $formatter = function ($item) {
            // 创建一个 ArrayIterator 来模拟 Traversable 接口
            return new \ArrayIterator([
                ['name' => 'Item ' . $item['id'] . '-1'],
                ['name' => 'Item ' . $item['id'] . '-2'],
            ]);
        };

        $result = $this->paginator->publicFetchList($query, $formatter);

        // 每个原始项都应生成 2 个格式化项，总共应有 4 个项
        $this->assertCount(4, $result['list']);
        $this->assertSame('Item 1-1', $result['list'][0]['name']);
        $this->assertSame('Item 1-2', $result['list'][1]['name']);
        $this->assertSame('Item 2-1', $result['list'][2]['name']);
        $this->assertSame('Item 2-2', $result['list'][3]['name']);
    }

    /**
     * 测试忽略非数组结果
     */
    public function testFetchList_withNonArrayResults(): void
    {
        $query = $this->createMock(Query::class);

        // 原始数据项
        $items = [1, 2, 3];
        $pagination = new MockPagination($items, 1, 10, 25);

        $this->knpPaginator->method('paginate')->willReturn($pagination);

        // 返回非数组结果的格式化函数（如字符串或 null）
        $formatter = function ($item) {
            if ($item === 1) {
                return ['id' => $item]; // 返回有效数组
            } elseif ($item === 2) {
                return 'string result'; // 返回字符串，应被忽略
            } else {
                return null; // 返回 null，应被忽略
            }
        };

        $result = $this->paginator->publicFetchList($query, $formatter);

        // 只有第一项应该被添加到列表中
        $this->assertCount(1, $result['list']);
        $this->assertSame(['id' => 1], $result['list'][0]);
    }

    /**
     * 测试自定义计数器回调
     */
    public function testFetchList_withCustomCounter(): void
    {
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
            fn($item) => $item,
            $counter
        );

        // 应使用自定义计数器返回的总数
        $this->assertSame(50, $result['pagination']['total']);
    }

    /**
     * 测试使用 QueryBuilder 作为参数
     */
    public function testFetchList_withQueryBuilder(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        // 原始数据项
        $items = [['id' => 1], ['id' => 2]];
        $pagination = new MockPagination($items, 1, 10, 25);

        $this->knpPaginator
            ->expects($this->once())
            ->method('paginate')
            ->with($queryBuilder, 1, 10)
            ->willReturn($pagination);

        $result = $this->paginator->publicFetchList($queryBuilder, fn($item) => $item);

        $this->assertCount(2, $result['list']);
    }

    /**
     * 测试自定义页大小
     */
    public function testFetchList_withCustomPageSize(): void
    {
        $query = $this->createMock(Query::class);

        // 创建一个自定义的 TestPaginator 覆盖 getDefaultPageSize
        $customPaginator = new class() extends TestPaginator {
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
            ->willReturn($pagination);

        $result = $customPaginator->publicFetchList($query, fn($item) => $item);

        $this->assertSame(20, $result['pagination']['pageSize']);
    }

    /**
     * 测试 emptyList 方法的默认值
     */
    public function testEmptyList_defaultValues(): void
    {
        $result = $this->paginator->publicEmptyList();

        $this->assertEmpty($result['list']);
        $this->assertSame(1, $result['pagination']['current']);
        $this->assertSame(10, $result['pagination']['pageSize']);
        $this->assertSame(0, $result['pagination']['total']);
        $this->assertFalse($result['pagination']['hasMore']);
    }

    /**
     * 测试 emptyList 方法的自定义值
     */
    public function testEmptyList_customValues(): void
    {
        // 设置自定义分页参数
        $this->paginator->currentPage = 3;
        $this->paginator->pageSize = 25;

        $result = $this->paginator->publicEmptyList();

        $this->assertEmpty($result['list']);
        $this->assertSame(3, $result['pagination']['current']);
        $this->assertSame(25, $result['pagination']['pageSize']);
        $this->assertSame(0, $result['pagination']['total']);
        $this->assertFalse($result['pagination']['hasMore']);
    }
}

/**
 * 用于测试的 PaginatorTrait 实现类
 * @internal
 */
class TestPaginator
{
    use PaginatorTrait;

    /**
     * 公开 protected 方法以便测试
     */
    public function publicGetDefaultPageSize(int $prevValue): int
    {
        return $this->getDefaultPageSize($prevValue);
    }

    /**
     * 公开 protected 方法以便测试
     */
    public function publicFetchList(Query|QueryBuilder $queryBuilder, callable $formatter, ?callable $counter = null): array
    {
        return $this->fetchList($queryBuilder, $formatter, $counter);
    }

    /**
     * 公开 protected 方法以便测试
     */
    public function publicEmptyList(): array
    {
        return $this->emptyList();
    }
}

/**
 * 模拟分页结果的类
 * @internal
 */
class MockPagination implements PaginationInterface, \IteratorAggregate
{
    private array $items;
    private int $currentPage;
    private int $itemsPerPage;
    private int $totalCount;
    private array $options = [];
    private array $customParameters = [];

    public function __construct(array $items, int $currentPage, int $itemsPerPage, int $totalCount)
    {
        $this->items = $items;
        $this->currentPage = $currentPage;
        $this->itemsPerPage = $itemsPerPage;
        $this->totalCount = $totalCount;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getCurrentPageNumber(): int
    {
        return $this->currentPage;
    }

    public function getItemNumberPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getTotalItemCount(): int
    {
        return $this->totalCount;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function getPaginationData(): array
    {
        return [];
    }

    public function getCustomParameters(): array
    {
        return $this->customParameters;
    }

    public function setCustomParameters(array $parameters): void
    {
        $this->customParameters = $parameters;
    }

    public function getCustomParameter(string $name): mixed
    {
        return $this->customParameters[$name] ?? null;
    }

    public function getPaginatorOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    public function setCurrentPageNumber(int $pageNumber): void
    {
        $this->currentPage = $pageNumber;
    }

    public function setItemNumberPerPage(int $numItemsPerPage): void
    {
        $this->itemsPerPage = $numItemsPerPage;
    }

    public function setItems(array|\Traversable $items): void
    {
        if ($items instanceof \Traversable) {
            $this->items = iterator_to_array($items);
        } else {
            $this->items = $items;
        }
    }

    public function setPaginatorOptions(array $options): void
    {
        $this->options = $options;
    }

    public function setTotalItemCount(int $numTotal): void
    {
        $this->totalCount = $numTotal;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }
}
