<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle\Tests\Procedure;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Tourze\JsonRPCPaginatorBundle\Procedure\PaginatorTrait;

/**
 * 用于测试的 PaginatorTrait 实现类.
 *
 * @internal
 */
class TestPaginator
{
    use PaginatorTrait;

    /**
     * 公开 protected 方法以便测试.
     */
    public function publicGetDefaultPageSize(int $prevValue): int
    {
        return $this->getDefaultPageSize($prevValue);
    }

    /**
     * 公开 protected 方法以便测试.
     *
     * @return array<string, mixed>
     */
    public function publicFetchList(Query|QueryBuilder $queryBuilder, callable $formatter, ?callable $counter = null): array
    {
        return $this->fetchList($queryBuilder, $formatter, $counter);
    }

    /**
     * 公开 protected 方法以便测试.
     *
     * @return array<string, mixed>
     */
    public function publicEmptyList(): array
    {
        return $this->emptyList();
    }
}
