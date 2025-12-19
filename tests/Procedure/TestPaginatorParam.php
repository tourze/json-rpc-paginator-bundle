<?php

declare(strict_types=1);

namespace Tourze\JsonRPCPaginatorBundle\Tests\Procedure;

use Tourze\JsonRPCPaginatorBundle\Param\PaginatorParamInterface;

/**
 * 用于测试的分页参数类.
 *
 * @internal
 */
readonly class TestPaginatorParam implements PaginatorParamInterface
{
    public function __construct(
        public int $pageSize = 10,
        public int $currentPage = 1,
        public ?int $lastId = null,
    ) {
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastId(): ?int
    {
        return $this->lastId;
    }
}
